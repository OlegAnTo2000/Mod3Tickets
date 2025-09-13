<?php

/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'model/modx/modprocessor.class.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'model/modx/processors/resource/create.class.php';

class TicketCreateProcessor extends modResourceCreateProcessor
{
	/** @var Ticket */
	public $object;
	public $classKey = 'Ticket';
	public $permission = 'ticket_save';
	public $languageTopics = ['access', 'resource', 'tickets:default'];
	/** @var TicketsSection */
	public $parentResource;
	private $_published;
	private $_sendEmails = false;

	/**
	 * @return array|string|null
	 */
	public function beforeSet()
	{
		$this->_published = $this->getProperty('published', null);
		if ($this->_published && !$this->modx->hasPermission('ticket_publish')) {
			return $this->modx->lexicon('ticket_err_publish');
		}

		// Required fields
		$requiredFields = $this->getProperty('requiredFields', ['parent', 'pagetitle', 'content']);
		foreach ($requiredFields as $field) {
			$value = \trim($this->getProperty($field));
			if (empty($value) && 'mgr' != $this->modx->context->key) {
				$this->addFieldError($field, $this->modx->lexicon('field_required'));
			} else {
				$this->setProperty($field, $value);
			}
		}
		$content = $this->getProperty('content');
		$length = \mb_strlen(\strip_tags($content), $this->modx->getOption('modx_charset', null, 'UTF-8', true));
		$max = $this->modx->getOption('tickets.ticket_max_cut', null, 1000, true);
		if (empty($content) && 'mgr' != $this->modx->context->key) {
			return $this->modx->lexicon('ticket_err_empty');
		} elseif ('mgr' != $this->modx->context->key && !\preg_match('#<cut\b.*?>#', $content) && $length > $max) {
			return $this->modx->lexicon('ticket_err_cut', ['length' => $length, 'max_cut' => $max]);
		}

		$set = parent::beforeSet();
		if ($this->hasErrors()) {
			return $this->modx->lexicon('ticket_err_form');
		}
		$this->unsetProperty('action');

		return $set;
	}

	public function setFieldDefaults()
	{
		$set = parent::setFieldDefaults();

		// Ticket properties
		$properties = 'mgr' == $this->modx->context->key
			? $this->getProperty('properties')
			: $this->parentResource->getProperties();
		$this->unsetProperty('properties');

		// Define introtext
		$introtext = $this->getProperty('introtext');
		if (empty($introtext)) {
			$introtext = $this->object->getIntroText($this->getProperty('content'), false);
		}
		if (empty($properties['disable_jevix'])) {
			$introtext = $this->object->sanitizeText($introtext);
		}

		$createdon = \time();
		// Redefine main parameters if we are not in the manager
		if ('mgr' == $this->modx->context->key) {
			$template = $this->getProperty('template');
			$hidemenu = $this->getProperty('hidemenu');
			$show_in_tree = $this->getProperty('show_in_tree');
			$createdby = $this->getProperty('createdby');
			$published = $this->getProperty('published');
			$publishedon = $this->getProperty('publishedon', $createdon);
			$publishedby = $this->getProperty('publishedby', $createdby);
		} else {
			$template = $properties['template'];
			$hidemenu = $properties['hidemenu'];
			$show_in_tree = $properties['show_in_tree'];
			$createdby = $this->modx->user->id;
			$published = $this->_published;
			$publishedon = $this->_published
				? $createdon
				: 0;
			$publishedby = $this->modx->user->id;
		}
		if (empty($template)) {
			$template = $this->modx->context->getOption(
				'tickets.default_template',
				$this->modx->context->getOption('default_template')
			);
		}

		$tmp = [
			'disable_jevix' => !empty($properties['disable_jevix']),
			'process_tags' => !empty($properties['process_tags']),
		];
		if (empty($published)) {
			$tmp['was_published'] = false;
		} else {
			$this->_sendEmails = true;
		}
		// Set properties
		$this->setProperties([
			'class_key' => 'Ticket',
			'published' => $published,
			'createdby' => $createdby,
			'createdon' => $createdon,
			'publishedby' => $publishedby,
			'publishedon' => $publishedon,
			'syncsite' => 0,
			'template' => $template,
			'introtext' => $introtext,
			'hidemenu' => $hidemenu,
			'show_in_tree' => $show_in_tree,
			'properties' => ['tickets' => $tmp],
		]);

		return $set;
	}

	/**
	 * @return string
	 */
	public function prepareAlias()
	{
		$alias = parent::prepareAlias();
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
	 * @return bool|string|null
	 */
	public function checkParentPermissions()
	{
		$parent = null;
		$parentId = \intval($this->getProperty('parent'));
		if ($parentId > 0) {
			$sections = $this->getProperty('sections');
			if (!empty($sections) && !\in_array($parentId, $sections, true)) {
				return $this->modx->lexicon('ticket_err_wrong_parent');
			}
			$this->parentResource = $this->modx->getObject(TicketsSection::class, $parentId);
			if ($this->parentResource) {
				if ('TicketsSection' != $this->parentResource->get('class_key')) {
					return $this->modx->lexicon('ticket_err_wrong_parent');
				} elseif (!$this->parentResource->checkPolicy(['section_add_children' => true])) {
					return $this->modx->lexicon('ticket_err_wrong_parent');
				}
			} else {
				return $this->modx->lexicon('resource_err_nfs', ['id' => $parentId]);
			}
		} else {
			return $this->modx->lexicon('ticket_err_access_denied');
		}

		return true;
	}

	/**
	 * @return bool|string|null
	 */
	public function beforeSave()
	{
		/** @var TicketsSection $section */
		if ($this->getProperty('published')) {
			if ($section = $this->modx->getObject(TicketsSection::class, $this->object->get('parent'))) {
				$ratings = $section->getProperties('ratings');
				if (isset($ratings['min_ticket_create']) && '' !== $ratings['min_ticket_create']) {
					if ($profile = $this->modx->getObject(TicketAuthor::class, $this->object->get('createdby'))) {
						$min = (float) $ratings['min_ticket_create'];
						$rating = $profile->get('rating');
						if ($rating < $min) {
							return $this->modx->lexicon('ticket_err_rating_ticket', ['rating' => $min]);
						}
					}
				}
			}
		}

		return true;
	}

	public function afterSave()
	{
		$uri = $this->object->get('uri');
		$new_uri = \str_replace('%id', $this->object->get('id'), $uri);
		if ($uri != $new_uri) {
			$this->object->set('uri', $new_uri);
			$this->object->save();
		}

		// Updating resourceMap before OnDocSaveForm event
		$results = $this->modx->cacheManager->generateContext(
			$this->object->context_key,
			['cache_context_settings' => false]
		);
		$this->modx->context->resourceMap = $results['resourceMap'];
		$this->modx->context->aliasMap = $results['aliasMap'];

		if ($this->_sendEmails && 'mgr' == $this->modx->context->key) {
			$this->sendTicketMails();
		}

		return parent::afterSave();
	}

	/**
	 * Call method for notify users about new ticket in section.
	 */
	protected function sendTicketMails()
	{
		/** @var Tickets $Tickets */
		if ($Tickets = $this->modx->getService('Tickets')) {
			$Tickets->config['tplTicketEmailBcc'] = 'tpl.Tickets.ticket.email.bcc';
			$Tickets->config['tplTicketEmailSubscription'] = 'tpl.Tickets.ticket.email.subscription';
			$Tickets->sendTicketMails($this->object->toArray());
		}
	}

	/**
	 * @return bool
	 */
	public function clearCache()
	{
		$clear = false;
		/** @var TicketsSection $section */
		if ($section = $this->object->getOne('Section')) {
			$section->clearCache();
			$clear = true;
		}

		// Clear context settings
		if ($this->object->get('published')) {
			/** @var xPDOFileCache $cache */
			$cache = $this->modx->cacheManager->getCacheProvider($this->modx->getOption(
				'cache_context_settings_key',
				null,
				'context_settings'
			));
			$key = $this->modx->context->getCacheKey();
			$cache->delete($key);
		}

		return $clear;
	}

	/**
	 * @return array
	 */
	public function addTemplateVariables()
	{
		if ('mgr' != $this->modx->context->key) {
			$values = [];
			$tvs = $this->object->getMany('TemplateVars');

			/** @var modTemplateVarResource $tv */
			foreach ($tvs as $tv) {
				$values['tv' . $tv->get('id')] = $this->getProperty($tv->get('name'), $tv->get('value'));
			}

			if (!empty($values)) {
				$this->setProperties($values);
				$this->setProperty('tvs', 1);
			}
		}

		return parent::addTemplateVariables();
	}

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
		$q = $this->modx->newQuery('TicketFile');
		$q->where(['class' => 'Ticket']);
		$q->andCondition(['parent' => 0, 'createdby' => $this->modx->user->id], null, 1);
		$q->sortby('createdon', 'ASC');
		$collection = $this->modx->getIterator('TicketFile', $q);

		$replace = [];
		$count = 0;
		/** @var TicketFile $item */
		foreach ($collection as $item) {
			if ($item->get('deleted')) {
				$replace[$item->get('url')] = '';
				$item->remove();
			} else {
				$old_url = $item->get('url');
				$item->set('parent', $this->object->get('id'));
				$item->save();
				$replace[$old_url] = [
					'url' => $item->get('url'),
					'thumb' => $item->get('thumb'),
					'thumbs' => $item->get('thumbs'),
				];
				++$count;
			}
		}

		// Update ticket links
		if (!empty($replace)) {
			$array = [
				'introtext' => $this->object->get('introtext'),
				'content' => $this->object->get('content'),
			];
			$update = false;
			foreach ($array as $field => $text) {
				$pcre = '#<a.*?>.*?</a>|<img.*?>#s';
				\preg_match_all($pcre, $text, $matches);
				$src = $dst = [];
				foreach ($matches[0] as $tag) {
					foreach ($replace as $from => $to) {
						if (false !== \strpos($tag, $from)) {
							if (\is_array($to)) {
								$src[] = $from;
								$dst[] = $to['url'];
								if (empty($to['thumbs'])) {
									$to['thumbs'] = [$to['thumb']];
								}
								foreach ($to['thumbs'] as $key => $thumb) {
									$src[] = \str_replace('/' . $this->object->id . '/', '/0/', $thumb);
									$dst[] = \str_replace('/0/', '/' . $this->object->id . '/', $thumb);
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
					$text = \str_replace($src, $dst, $text);
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
