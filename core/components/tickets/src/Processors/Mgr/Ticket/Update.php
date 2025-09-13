<?php

namespace Tickets\Processors\Mgr\Ticket;

use function in_array;
use function intval;
use function is_array;
use function is_null;
use function mb_strlen;

use MODX\Revolution\modResource;
use MODX\Revolution\modTemplateVarResource;
use MODX\Revolution\Processors\Resource\Update as ResourceUpdate;

use function preg_match;
use function preg_match_all;
use function preg_replace;
use function str_replace;
use function strip_tags;
use function strpos;

use Tickets\Model\Ticket;
use Tickets\Model\TicketAuthor;
use Tickets\Model\TicketFile;
use Tickets\Model\TicketsSection;

use function tickets_service;
use function time;
use function trim;

class Update extends ResourceUpdate
{
	/** @var Ticket */
	public $object;
	public $classKey       = Ticket::class;
	public $permission     = 'ticket_save';
	public $languageTopics = ['resource', 'tickets:default'];
	private $_published;
	private $_sendEmails = false;

	/**
	 * @return bool|string|null
	 */
	public function initialize()
	{
		$primaryKey = $this->getProperty($this->primaryKeyField, false);
		if (empty($primaryKey)) {
			return $this->modx->lexicon($this->objectType . '_err_ns');
		}

		if (
			!$this->modx->getCount($this->classKey, [
				'id'        => $primaryKey,
				'class_key' => $this->classKey,
			]) && $res = $this->modx->getObject(modResource::class, ['id' => $primaryKey])
		) {
			$res->set('class_key', $this->classKey);
			$res->save();
		}

		return parent::initialize();
	}

	/**
	 * @return bool|string|null
	 */
	public function beforeSet()
	{
		$this->_published = $this->getProperty('published', null);
		if ($this->_published && !$this->modx->hasPermission('ticket_publish')) {
			return $this->modx->lexicon('ticket_err_publish');
		}

		if ($this->object->createdby != $this->modx->user->id && !$this->modx->hasPermission('edit_document')) {
			return $this->modx->lexicon('ticket_err_wrong_user');
		}

		// Required fields
		$requiredFields = $this->getProperty('requiredFields', ['parent', 'pagetitle', 'content']);
		foreach ($requiredFields as $field) {
			$value = trim($this->getProperty($field));
			if (empty($value) && 'mgr' != $this->modx->context->key) {
				$this->addFieldError($field, $this->modx->lexicon('field_required'));
			} else {
				$this->setProperty($field, $value);
			}
		}
		$content = $this->getProperty('content');
		$length  = mb_strlen(strip_tags($content), $this->modx->getOption('modx_charset', null, 'UTF-8', true));
		$max     = $this->modx->getOption('tickets.ticket_max_cut', null, 1000, true);
		if (empty($content) && 'mgr' != $this->modx->context->key) {
			return $this->modx->lexicon('ticket_err_empty');
		} elseif ('mgr' != $this->modx->context->key && !preg_match('#<cut\b.*?>#', $content) && $length > $max) {
			return $this->modx->lexicon('ticket_err_cut', ['length' => $length, 'max_cut' => $max]);
		}

		$set = parent::beforeSet();
		if ($this->hasErrors()) {
			return $this->modx->lexicon('ticket_err_form');
		}
		$this->setFieldDefault();
		$this->unsetProperty('action');

		return $set;
	}

	/**
	 * @return bool
	 */
	public function setFieldDefault()
	{
		// Ticket properties
		$properties = 'mgr' == $this->modx->context->key
			? $this->getProperty('properties')
			: $this->object->getProperties();
		$this->unsetProperty('properties');

		// Define introtext
		$introtext = $this->getProperty('introtext');
		if (empty($introtext)) {
			$introtext = $this->object->getIntroText($this->getProperty('content'), false);
		}
		if (empty($properties['disable_jevix'])) {
			$introtext = $this->object->sanitizeText($introtext);
		}

		// Set properties
		if ('mgr' != $this->modx->context->key) {
			$this->unsetProperty('properties');
			$this->unsetProperty('published');
			$tmp      = $this->parentResource->getProperties();
			$template = $tmp['template'];
			if (empty($template)) {
				$template = $this->modx->context->getOption(
					'tickets.default_template',
					$this->modx->context->getOption('default_template')
				);
			}
			$this->setProperty('template', $template);
		}
		$this->setProperties([
			'class_key' => Ticket::class,
			'syncsite'  => 0,
			'introtext' => $introtext,
		]);
		if ('mgr' != $this->modx->context->key && !is_null($this->_published)) {
			$this->setProperty('published', $this->_published);
		}
		if ('mgr' == $this->modx->context->key) {
			$properties['disable_jevix'] = !empty($properties['disable_jevix']);
			$properties['process_tags']  = !empty($properties['process_tags']);
			$this->object->setProperties($properties, 'tickets', true);
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function beforeSave()
	{
		$time = time();
		if ($this->_published) {
			$properties = $this->object->getProperties();
			// First publication
			if (isset($properties['was_published']) && empty($properties['was_published'])) {
				$this->object->set('createdon', $time, 'integer');
				$this->object->set('publishedon', $time, 'integer');
				unset($properties['was_published']);
				$this->object->set('properties', $properties);
				$this->_sendEmails = true;

				/** @var TicketsSection $section */
				if ($section = $this->object->getOne('Section')) {
					/** @var TicketsSection $section */
					$ratings = $section->getProperties('ratings');
					if (isset($ratings['min_ticket_create']) && '' !== $ratings['min_ticket_create']) {
						if ($profile = $this->modx->getObject(TicketAuthor::class, $this->object->get('createdby'))) {
							$min    = (float) $ratings['min_ticket_create'];
							$rating = $profile->get('rating');
							if ($rating < $min) {
								return $this->modx->lexicon('ticket_err_rating_ticket', ['rating' => $min]);
							}
						}
					}
				}
			}
		}
		$this->object->set('editedby', $this->modx->user->get('id'));
		$this->object->set('editedon', $time, 'integer');

		return !$this->hasErrors();
	}

	/**
	 * @return bool
	 */
	public function afterSave()
	{
		$parent = parent::afterSave();
		if ($this->_sendEmails && 'mgr' == $this->modx->context->key) {
			$this->sendTicketMails();
		}

		return $parent;
	}

	/**
	 * Call method for notify users about new ticket in section.
	 */
	protected function sendTicketMails()
	{
		/** @var Tickets $Tickets */
		if ($Tickets = tickets_service()) {
			$Tickets->config['tplTicketEmailBcc']          = 'tpl.Tickets.ticket.email.bcc';
			$Tickets->config['tplTicketEmailSubscription'] = 'tpl.Tickets.ticket.email.subscription';
			$Tickets->sendTicketMails($this->object->toArray());
		}
	}

	/**
	 * @return mixed|string
	 */
	public function checkFriendlyAlias()
	{
		$alias = parent::checkFriendlyAlias();

		if ('mgr' != $this->modx->context->key) {
			foreach ($this->modx->error->errors as $k => $v) {
				if ('alias' == $v['id'] || 'uri' == $v['id']) {
					unset($this->modx->error->errors[$k]);
				}
			}
		}

		return $alias;
	}

	/**
	 * @return int|mixed|string|null
	 */
	public function handleParent()
	{
		if ('manager' == $this->modx->context->key) {
			return parent::handleParent();
		}

		$parent   = null;
		$parentId = intval($this->getProperty('parent'));
		if ($parentId > 0) {
			$sections = $this->getProperty('sections');
			if (!empty($sections) && !in_array($parentId, $sections, true)) {
				return $this->modx->lexicon('ticket_err_wrong_parent');
			}
			$this->parentResource = $this->modx->getObject(TicketsSection::class, $parentId);
			if ($this->parentResource) {
				if (TicketsSection::class != $this->parentResource->get('class_key')) {
					$this->addFieldError('parent', $this->modx->lexicon('ticket_err_wrong_parent'));
				} elseif (!$this->parentResource->checkPolicy(['section_add_children' => true])) {
					$this->addFieldError('parent', $this->modx->lexicon('ticket_err_wrong_parent'));
				}
			} else {
				$this->addFieldError('parent', $this->modx->lexicon('resource_err_nfs', ['id' => $parentId]));
			}
		}

		return $parent;
	}

	/**
	 * @return bool
	 */
	public function checkPublishingPermissions()
	{
		if ('mgr' == $this->modx->context->key) {
			return parent::checkPublishingPermissions();
		}

		return true;
	}

	public function clearCache()
	{
		$this->object->clearCache();
		/** @var TicketsSection $section */
		if ($section = $this->object->getOne('Section')) {
			/* @var TicketsSection $section */
			$section->clearCache();
		}
	}

	/**
	 * @return array|mixed
	 */
	public function saveTemplateVariables()
	{
		if ('mgr' != $this->modx->context->key) {
			$values = [];
			$tvs    = $this->object->getMany('TemplateVariables');

			/** @var modTemplateVarResource $tv */
			foreach ($tvs as $tv) {
				$values['tv' . $tv->get('id')] = $this->getProperty($tv->get('name'), $tv->get('value'));
			}

			if (!empty($values)) {
				$this->setProperties($values);
				$this->setProperty('tvs', 1);
			}
		}

		return parent::saveTemplateVariables();
	}

	/**
	 * @return array
	 */
	public function cleanup()
	{
		$this->processFiles();

		return parent::cleanup();
	}

	/**
	 * Add uploaded files to ticket.
	 *
	 * @return bool|int
	 */
	public function processFiles()
	{
		$q = $this->modx->newQuery(TicketFile::class);
		$q->where(['class' => Ticket::class]);
		$q->andCondition(['parent' => $this->object->id, 'createdby' => $this->modx->user->id], null, 1);
		$q->sortby('createdon', 'ASC');
		$collection = $this->modx->getIterator(TicketFile::class, $q);

		$replace = [];
		$count   = 0;
		/** @var TicketFile $item */
		foreach ($collection as $item) {
			if ($item->get('deleted')) {
				$replace[$item->get('url')] = '';
				$item->remove();
			} else {
				$old_url = $item->get('url');
				$item->set('parent', $this->object->id);
				$item->save();
				$replace[$old_url] = [
					'url'    => $item->get('url'),
					'thumb'  => $item->get('thumb'),
					'thumbs' => $item->get('thumbs'),
				];
				++$count;
			}
		}

		// Update ticket links
		if (!empty($replace)) {
			$array = [
				'introtext' => $this->object->get('introtext'),
				'content'   => $this->object->get('content'),
			];
			$update = false;
			foreach ($array as $field => $text) {
				$pcre = '#<a.*?>.*?</a>|<img.*?>#s';
				preg_match_all($pcre, $text, $matches);
				$src = $dst = [];
				foreach ($matches[0] as $tag) {
					foreach ($replace as $from => $to) {
						if (false !== strpos($tag, $from)) {
							if (is_array($to)) {
								$src[] = $from;
								$dst[] = $to['url'];
								if (empty($to['thumbs'])) {
									$to['thumbs'] = [$to['thumb']];
								}
								foreach ($to['thumbs'] as $key => $thumb) {
									if (false === strpos($thumb, '/' . $key . '/')) {
										// Old thumbnails
										$src[] = preg_replace('#\.[a-z]+$#i', '_thumb$0', $from);
										$dst[] = preg_replace('#\.[a-z]+$#i', '_thumb$0', $thumb);
									} else {
										// New thumbnails
										$src[] = str_replace('/' . $this->object->id . '/', '/0/', $thumb);
										$dst[] = str_replace('/0/', '/' . $this->object->id . '/', $thumb);
									}
								}
							} else {
								$src[] = $tag;
								$dst[] = '';
							}
							break;
						}
					}
				}
				if (!empty($src)) {
					$text = str_replace($src, $dst, $text);
					if ($this->object->$field != $text) {
						$this->object->set($field, $text);
						$update = true;
					}
				}
			}
			if ($update) {
				$this->object->save();
			}
		}

		return $count;
	}
}
