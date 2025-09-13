<?php

namespace Tickets\Model;

use function array_key_exists;
use function class_exists;
use function end;
use function explode;
use function file_put_contents;
use function is_array;
use function is_numeric;
use function json_decode;

use MODX\Revolution\modPhpThumb;
use MODX\Revolution\modX;
use MODX\Revolution\Sources\modMediaSource;

use function preg_replace;
use function print_r;
use function strpos;
use function tempnam;
use function unlink;

use xPDO\Om\xPDOSimpleObject;

/**
 * @property int $id
 */
class TicketFile extends xPDOSimpleObject
{
	/** @var modPhpThumb */
	public $phpThumb;
	/** @var modMediaSource */
	public $mediaSource;

	/**
	 * @return bool|string
	 */
	public function prepareSource(?modMediaSource $mediaSource = null)
	{
		if ($mediaSource) {
			$this->mediaSource = $mediaSource;
		} elseif (empty($this->mediaSource) && $source = $this->get('source')) {
			/** @var modMediaSource $mediaSource */
			if ($mediaSource = $this->xpdo->getObject(modMediaSource::class, ['id' => $source])) {
				/* @noinspection PhpUndefinedFieldInspection */
				/** @var modMediaSource $mediaSource */
				$mediaSource->set('ctx', $this->xpdo->context->key);
				$mediaSource->initialize();
				$this->mediaSource = $mediaSource;
			} else {
				return 'Could not initialize media source with id = ' . $source;
			}
		}

		return !empty($this->mediaSource) && $this->mediaSource instanceof modMediaSource;
	}

	/**
	 * @return bool|string
	 */
	public function generateThumbnails(?modMediaSource $mediaSource = null)
	{
		if ('image' != $this->get('type')) {
			return true;
		}

		$prepare = $this->prepareSource($mediaSource);
		if (true !== $prepare) {
			return $prepare;
		}

		$this->mediaSource->errors = [];
		$filename                  = $this->get('path') . $this->get('file');
		$info                      = $this->mediaSource->getObjectContents($filename);
		if (!is_array($info)) {
			return "[Tickets] Could not retrieve contents of file {$filename} from media source.";
		} elseif (!empty($this->mediaSource->errors['file'])) {
			return "[Tickets] Could not retrieve file {$filename} from media source: " . $this->mediaSource->errors['file'];
		}

		$properties = $this->mediaSource->getProperties();
		$thumbnails = [];
		if (array_key_exists('thumbnails', $properties) && !empty($properties['thumbnails']['value'])) {
			$thumbnails = json_decode($properties['thumbnails']['value'], true);
		} elseif (array_key_exists('thumbnail', $properties) && !empty($properties['thumbnail']['value'])) {
			$thumbnails = json_decode($properties['thumbnail']['value'], true);
		}

		if (empty($thumbnails)) {
			$thumbnails = [
				'thumb' => [
					'w'  => 120,
					'h'  => 90,
					'q'  => 90,
					'zc' => 1,
					'bg' => '000000',
					'f'  => !empty($properties['thumbnailType']['value'])
						? $properties['thumbnailType']['value']
						: 'jpg',
				],
			];
		}

		foreach ($thumbnails as $k => $options) {
			if (empty($options['f'])) {
				$options['f'] = !empty($properties['thumbnailType']['value'])
					? $properties['thumbnailType']['value']
					: 'jpg';
			}
			$options['name'] = !is_numeric($k)
				? $k
				: 'thumb';
			if ($image = $this->makeThumbnail($options, $info)) {
				$this->saveThumbnail($image, $options);
			}
		}

		return true;
	}

	/**
	 * @deprecated
	 *
	 * @return bool|string
	 */
	public function generateThumbnail(?modMediaSource $mediaSource = null)
	{
		return $this->generateThumbnails($mediaSource);
	}

	/**
	 * @param array $options
	 *
	 * @return bool|null
	 */
	public function makeThumbnail($options = [], array $info)
	{
		if (!class_exists('modPhpThumb')) {
			/** @noinspection PhpIncludeInspection */
			require MODX_CORE_PATH . 'model/phpthumb/modphpthumb.class.php';
		}
		/** @noinspection PhpParamsInspection */
		$phpThumb = new modPhpThumb($this->xpdo);
		$phpThumb->initialize();

		$tf = tempnam(MODX_BASE_PATH, 'tkt_');
		file_put_contents($tf, $info['content']);
		$phpThumb->setSourceFilename($tf);
		foreach ($options as $k => $v) {
			$phpThumb->setParameter($k, $v);
		}

		if ($phpThumb->GenerateThumbnail()) {
			if ($phpThumb->RenderOutput()) {
				@unlink($phpThumb->sourceFilename);
				@unlink($tf);
				$this->xpdo->log(modX::LOG_LEVEL_INFO, '[Tickets] phpThumb messages for "' . $this->get('url') . '". ' .
					print_r($phpThumb->debugmessages, 1));

				return $phpThumb->outputImageData;
			}
		}
		@unlink($phpThumb->sourceFilename);
		@unlink($tf);

		$this->xpdo->log(modX::LOG_LEVEL_ERROR, '[Tickets] Could not generate thumbnail for "' .
			$this->get('url') . '". ' . print_r($phpThumb->debugmessages, 1));

		return false;
	}

	/**
	 * @param array $options
	 *
	 * @return bool
	 */
	public function saveThumbnail($raw_image, $options = [])
	{
		$filename = preg_replace('#\.[a-z]+$#i', '', $this->get('file')) . '.' . $options['f'];
		$name     = !empty($options['name'])
			? $options['name']
			: 'thumb';
		$thumb_dir = preg_replace('#[^\w]#', '', $name);
		$path      = $this->get('path') . $thumb_dir . '/';

		$this->mediaSource->createContainer($path, '/');
		if ($file = $this->mediaSource->createObject($path, $filename, $raw_image)) {
			$url = $this->mediaSource->getObjectUrl($path . $filename);
			// Add thumbs
			$thumbs = $this->get('thumbs');
			if (!is_array($thumbs)) {
				$thumbs = [];
			}
			$thumbs[$name] = $url;
			$this->set('thumbs', $thumbs);
			// Main thumb
			if ('thumb' == $name) {
				$this->set('thumb', $url);
			}

			return $this->save();
		}

		return false;
	}

	/**
	 * @param null $cacheFlag
	 *
	 * @return bool
	 */
	public function save($cacheFlag = null)
	{
		if ($this->isDirty('parent')) {
			if ($this->prepareSource()) {
				$old_path = $this->get('path');
				$file     = $this->get('file');
				$new_path = $this->get('parent') . '/';

				$this->mediaSource->createContainer($new_path, '/');
				if ($this->mediaSource->moveObject($old_path . $file, $new_path)) {
					$this->set('path', $new_path);
					$this->set('url', $this->mediaSource->getObjectUrl($new_path . $file));
				}
				if (!$thumbs = $this->get('thumbs')) {
					$thumbs = ['thumb' => $this->get('thumb')];
				}
				foreach ($thumbs as $key => $thumb) {
					if (empty($thumb)) {
						continue;
					}
					if (false !== strpos($thumb, '/' . $key . '/')) {
						$old_path_thumb = $old_path . $key . '/';
						$new_path_thumb = $new_path . $key . '/';
						$this->mediaSource->createContainer($new_path_thumb, '/');
					} else {
						$old_path_thumb = $old_path;
						$new_path_thumb = $new_path;
					}
					$tmp   = explode('/', $thumb);
					$thumb = end($tmp);
					if ($this->mediaSource->moveObject($old_path_thumb . $thumb, $new_path_thumb)) {
						$thumbs[$key] = $this->mediaSource->getObjectUrl($new_path_thumb . $thumb);
						if ('thumb' == $key) {
							$this->set('thumb', $this->mediaSource->getObjectUrl($new_path_thumb . $thumb));
						}
					}
				}
				$this->set('thumbs', $thumbs);
			}
		}

		return parent::save($cacheFlag);
	}

	/**
	 * @return bool
	 */
	public function remove(array $ancestors = [])
	{
		if (true === $this->prepareSource()) {
			if ($this->mediaSource->removeObject($this->get('path') . $this->get('file'))) {
				if (!$thumbs = $this->get('thumbs')) {
					$thumbs = ['thumb' => $this->get('thumb')];
				}
				foreach ($thumbs as $key => $thumb) {
					if (empty($thumb)) {
						continue;
					}
					$path = false !== strpos($thumb, '/' . $key . '/')
						? $this->get('path') . $key . '/'
						: $this->get('path');
					$tmp      = explode('/', $thumb);
					$filename = end($tmp);
					$this->mediaSource->removeObject($path . $filename);
				}
			}
		}

		return parent::remove($ancestors);
	}
}
