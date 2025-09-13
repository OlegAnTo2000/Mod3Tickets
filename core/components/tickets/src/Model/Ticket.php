<?php

namespace Tickets\Model;

use function abs;
use function array_key_exists;
use function array_merge;
use function array_reverse;
use function array_search;
use function boolval;
use function date;
use function explode;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function is_object;
use function is_string;
use function microtime;

use MODX\Revolution\modAccessibleObject;
use MODX\Revolution\modContentType;
use MODX\Revolution\modResource;
use PDO;

use function preg_match;
use function preg_replace;
use function reset;
use function rtrim;
use function str_replace;
use function strpos;
use function strtotime;
use function tickets_service;

use xPDO\Om\xPDOObject;
use xPDO\xPDO;

class Ticket extends modResource
{
	public $showInContextMenu      = false;
	public $allowChildrenResources = false;
	private $_oldAuthor            = 0;

	/**
	 * @param string $className
	 * @param null   $criteria
	 * @param bool   $cacheFlag
	 *
	 * @return modAccessibleObject|object|null
	 */
	public static function load(xPDO &$xpdo, $className, $criteria = null, $cacheFlag = true)
	{
		if (!is_object($criteria)) {
			$criteria = $xpdo->getCriteria($className, $criteria, $cacheFlag);
		}
		/* @noinspection PhpParamsInspection */
		$xpdo->addDerivativeCriteria($className, $criteria);

		return parent::load($xpdo, $className, $criteria, $cacheFlag);
	}

	/**
	 * @param string $className
	 * @param null   $criteria
	 * @param bool   $cacheFlag
	 *
	 * @return array
	 */
	public static function loadCollection(xPDO &$xpdo, $className, $criteria = null, $cacheFlag = true)
	{
		if (!is_object($criteria)) {
			$criteria = $xpdo->getCriteria($className, $criteria, $cacheFlag);
		}
		/* @noinspection PhpParamsInspection */
		$xpdo->addDerivativeCriteria($className, $criteria);

		return parent::loadCollection($xpdo, $className, $criteria, $cacheFlag);
	}

	/**
	 * @return string
	 */
	public static function getControllerPath(xPDO &$modx)
	{
		return $modx->getOption(
			'tickets.core_path',
			null,
			$modx->getOption('core_path') . 'components/tickets/'
		) . 'controllers/ticket/';
	}

	/**
	 * @return array
	 */
	public function getContextMenuText()
	{
		/** @var xPDO $xpdo */
		$this->xpdo->lexicon->load('tickets:default');

		return [
			'text_create'      => $this->xpdo->lexicon('tickets'),
			'text_create_here' => $this->xpdo->lexicon('ticket_create_here'),
		];
	}

	/**
	 * @return string|null
	 */
	public function getResourceTypeName()
	{
		/** @var xPDO $xpdo */
		$this->xpdo->lexicon->load('tickets:default');

		return $this->xpdo->lexicon('ticket');
	}

	/**
	 * @param array|string $k
	 * @param null         $format
	 * @param null         $formatTemplate
	 *
	 * @return int|mixed|string
	 */
	public function get($k, $format = null, $formatTemplate = null)
	{
		$fields = ['comments', 'views', 'stars', 'rating', 'date_ago'];

		if (is_array($k)) {
			$values = [];
			foreach ($k as $v) {
				$values[$v] = $this->get($v, $format, $formatTemplate);
			}

			return $values;
		} else {
			switch ($k) {
				case 'comments':
					$values = $this->_getVirtualFields();
					$value  = $values['comments'];
					break;
				case 'views':
					$values = $this->_getVirtualFields();
					$value  = $values['views'];
					break;
				case 'stars':
					$values = $this->_getVirtualFields();
					$value  = $values['stars'];
					break;
				case 'rating':
					$values = $this->_getVirtualFields();
					$value  = $values['rating'];
					break;
				case 'date_ago':
					$value = $this->getDateAgo();
					break;
				default:
					/** @var modResource $this */
					$value = parent::get($k, $format, $formatTemplate);
			}

			if (isset($this->_fieldMeta[$k]) && 'string' == $this->_fieldMeta[$k]['phptype']) {
				$properties = $this->getProperties();
				if (!$properties['process_tags'] && !in_array($k, $fields, true)) {
					$value = str_replace(
						['[', ']', '`', '{', '}'],
						['&#91;', '&#93;', '&#96;', '&#123;', '&#125;'],
						$value
					);
				}
			}
		}

		return $value;
	}

	/**
	 * @param string $keyPrefix
	 * @param bool   $rawValues
	 * @param bool   $excludeLazy
	 * @param bool   $includeRelated
	 *
	 * @return array
	 */
	public function toArray($keyPrefix = '', $rawValues = false, $excludeLazy = false, $includeRelated = false)
	{
		$fields = $this->_getVirtualFields();
		if (!empty($keyPrefix)) {
			foreach ($fields as $k => $v) {
				$fields[$keyPrefix . $k] = $v;
				unset($fields[$k]);
			}
		}

		$array = array_merge(
			parent::toArray($keyPrefix, $rawValues, $excludeLazy, $includeRelated),
			$fields
		);

		return $array;
	}

	/**
	 * @return string
	 */
	public function process()
	{
		if ($this->privateweb && !$this->xpdo->hasPermission('ticket_view_private') && $id = $this->getOption('tickets.private_ticket_page')) {
			$this->xpdo->sendForward($id);
			exit;
		} else {
			return parent::process();
		}
	}

	public function getContent(array $options = [])
	{
		$content    = parent::get('content');
		$properties = $this->getProperties();
		$content    = tickets_service()->sanitizeText($content, false);

		if (!$properties['process_tags']) {
			$content = str_replace(
				['[', ']', '`', '{', '}'],
				['&#91;', '&#93;', '&#96;', '&#123;', '&#125;'],
				$content
			);
		}
		$content = preg_replace('/<cut(.*?)>/i', '<a name="cut"></a>', $content);

		return $content;
	}

	/**
	 * Html filter and typograf.
	 *
	 * @var mixed for processing
	 * @var bool
	 *
	 * @return mixed Filtered text
	 */
	public function Jevix($text, $replaceTags = true)
	{
		/** @var Tickets $Tickets */
		if ($Tickets = $this->xpdo->getService('Tickets')) {
			return $Tickets->sanitizeText($text, $replaceTags);
		}

		return 'Error on loading class "Tickets".';
	}

	/**
	 * Generate intro text from content buy cutting text before tag <cut/>.
	 *
	 * @param string $content Any text for processing, with tag <cut/>
	 * @param bool   $jevix
	 *
	 * @return mixed $introtext
	 */
	public function getIntroText($content = null, $jevix = true)
	{
		if (empty($content)) {
			$content = parent::get('content');
		}
		$content = preg_replace('/<cut(.*?)>/i', '<cut/>', $content);

		if (!preg_match('/<cut\/>/', $content)) {
			$introtext = $content;
		} else {
			$tmp       = explode('<cut/>', $content);
			$introtext = reset($tmp);
			if ($Tickets = tickets_service()) {
				$introtext = $Tickets->sanitizeText($introtext, true);
			}
		}

		return $introtext;
	}

	/**
	 * @param string $alias
	 * @param null   $criteria
	 * @param bool   $cacheFlag
	 *
	 * @return array
	 */
	public function &getMany($alias, $criteria = null, $cacheFlag = true)
	{
		if ('Files' == $alias || 'Votes' == $alias) {
			$criteria = ['class' => $this->class_key];
		}

		return parent::getMany($alias, $criteria, $cacheFlag);
	}

	/**
	 * @param string $alias
	 *
	 * @return bool
	 */
	public function addMany(&$obj, $alias = '')
	{
		$added = false;
		if (is_array($obj)) {
			foreach ($obj as $o) {
				/** @var xPDOObject $o */
				if (is_object($o)) {
					$o->set('class', $this->class_key);
					$added = parent::addMany($obj, $alias);
				}
			}

			return $added;
		} else {
			return parent::addMany($obj, $alias);
		}
	}

	/**
	 * Shorthand for getting virtual Ticket fields.
	 *
	 * @return array $array Array with virtual fields
	 */
	protected function _getVirtualFields()
	{
		/** @var TicketTotal $total */
		if (!$total = $this->getOne('Total')) {
			/** @var TicketTotal $total */
			$total = $this->xpdo->newObject(TicketTotal::class);
			$total->fromArray([
				'id'    => $this->id,
				'class' => Ticket::class,
			], '', true, true);
			$total->fetchValues();
			$total->save();
		}

		return $total->get([
			'comments',
			'views',
			'stars',
			'rating',
			'rating_plus',
			'rating_minus',
		]);
	}

	/**
	 * Returns count of views of Ticket by users.
	 *
	 * @return int $count Total count of views
	 */
	public function getViewsCount()
	{
		return $this->xpdo->getCount(TicketView::class, ['parent' => $this->id]);
	}

	/**
	 * Returns count of comments to Ticket.
	 *
	 * @return int $count Total count of comment
	 */
	public function getCommentsCount()
	{
		$q = $this->xpdo->newQuery(TicketThread::class, ['name' => 'resource-' . $this->id]);
		$q->leftJoin(
			TicketComment::class,
			'TicketComment',
			'`TicketThread`.`id` = `TicketComment`.`thread` AND `TicketComment`.`published` = 1'
		);
		$q->select('COUNT(`TicketComment`.`id`) as `comments`');

		$count  = 0;
		$tstart = microtime(true);
		if ($q->prepare() && $q->stmt->execute()) {
			$this->xpdo->startTime += microtime(true) - $tstart;
			++$this->xpdo->executedQueries;
			$count = (int) $q->stmt->fetchColumn();
		}

		return $count;
	}

	/**
	 * Returns number of stars for Ticket.
	 *
	 * @return int
	 */
	public function getStarsCount()
	{
		return $this->xpdo->getCount('TicketStar', ['id' => $this->id, 'class' => 'Ticket']);
	}

	/**
	 * Return formatted date of ticket creation.
	 *
	 * @return string
	 */
	public function getDateAgo()
	{
		$createdon = parent::get('createdon');
		/** @var Tickets $Tickets */
		if ($Tickets = $this->xpdo->getService('Tickets')) {
			$createdon = $Tickets->dateFormat($createdon);
		}

		return $createdon;
	}

	/**
	 * Returns vote of current user for this ticket.
	 *
	 * @return int|mixed
	 */
	public function getVote()
	{
		$q = $this->xpdo->newQuery('TicketVote');
		$q->where([
			'id'        => $this->id,
			'createdby' => $this->xpdo->user->id,
			'class'     => 'Ticket',
		]);
		$q->select('`value`');

		$vote   = 0;
		$tstart = microtime(true);
		if ($q->prepare() && $q->stmt->execute()) {
			$this->xpdo->startTime += microtime(true) - $tstart;
			++$this->xpdo->executedQueries;
			$vote = $q->stmt->fetchColumn();
		}

		return $vote;
	}

	/**
	 * Get rating.
	 *
	 * @return array
	 */
	public function getRating()
	{
		$rating = ['rating' => 0, 'rating_plus' => 0, 'rating_minus' => 0];

		$q = $this->xpdo->newQuery('TicketVote');
		$q->innerJoin('Ticket', 'Ticket', 'Ticket.id = TicketVote.id');
		$q->where([
			'class'            => 'Ticket',
			'id'               => $this->id,
			'Ticket.deleted'   => 0,
			'Ticket.published' => 1,
		]);
		$q->select('value');
		$tstart = microtime(true);
		if ($q->prepare() && $q->stmt->execute()) {
			$this->xpdo->startTime += microtime(true) - $tstart;
			++$this->xpdo->executedQueries;
			$rows = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
			foreach ($rows as $value) {
				$rating['rating'] += $value;
				if ($value > 0) {
					$rating['rating_plus'] += $value;
				} elseif ($value < 0) {
					$rating['rating_minus'] += abs($value);
				}
			}
		}

		return $rating;
	}

	/**
	 * Build custom uri with respect to section settings.
	 *
	 * @param string $alias
	 *
	 * @return string|bool
	 */
	public function setUri($alias = '')
	{
		/*
		if (!$this->get('published')) {
			$this->set('uri', '');
			$this->set('uri_override', 0);
			return true;
		}
		*/

		if (empty($alias)) {
			$alias = $this->get('alias');
		}
		/** @var TicketsSection $section */
		if ($section = $this->xpdo->getObject(TicketsSection::class, $this->get('parent'))) {
			$properties = $section->getProperties();
		} else {
			return false;
		}
		$template = $properties['uri'];
		if (empty($template) || false === strpos($template, '%')) {
			return false;
		}

		if ($this->get('pub_date')) {
			$date = $this->get('pub_date');
		} else {
			$date = $this->get('published')
				? $this->get('publishedon')
				: $this->get('createdon');
		}
		$date = strtotime($date);

		$pls = [
			'pl' => ['%y', '%m', '%d', '%id', '%alias', '%ext'],
			'vl' => [
				date('y', $date),
				date('m', $date),
				date('d', $date),
				$this->get('id')
					? $this->get('id')
					: '%id',
				$alias,
			],
		];

		/** @var modContentType $contentType */
		if ($contentType = $this->xpdo->getObject(modContentType::class, $this->get('content_type'))) {
			/** @var modContentType $contentType */
			$pls['vl'][] = $contentType->getExtension();
		} else {
			$pls['vl'][] = '';
		}

		$uri = rtrim($section->getAliasPath($section->get('alias')), '/') . '/' . str_replace(
			$pls['pl'],
			$pls['vl'],
			$template
		);
		$this->set('uri', $uri);
		$this->set('uri_override', true);

		return $uri;
	}

	/**
	 * Get the properties for the specific namespace for the Resource.
	 *
	 * @param string $namespace
	 *
	 * @return array
	 */
	public function getProperties($namespace = 'tickets')
	{
		$properties = parent::getProperties($namespace);

		// Convert old settings
		if (empty($this->reloadOnly)) {
			$flag = false;
			$tmp  = ['disable_jevix', 'process_tags', 'rating'];
			if ($old = parent::get('properties')) {
				foreach ($tmp as $v) {
					if (array_key_exists($v, $old)) {
						$properties[$v] = $old[$v];
						$flag           = true;
						unset($old[$v]);
					}
				}
				if ($flag) {
					$old['tickets'] = $properties;
					$this->set('properties', $old);
					$this->save();
				}
			}
		}
		// --

		if (empty($properties)) {
			/** @var TicketsSection $parent */
			if (!$parent = $this->getOne('Parent')) {
				$parent = $this->xpdo->newObject(TicketsSection::class);
			}
			/** @var TicketsSection $parent */
			$default_properties = $parent->getProperties($namespace);
			if (!empty($default_properties)) {
				foreach ($default_properties as $key => $value) {
					if (!isset($properties[$key])) {
						$properties[$key] = $value;
					} elseif ('true' === $properties[$key]) {
						$properties[$key] = true;
					} elseif ('false' === $properties[$key]) {
						$properties[$key] = false;
					} elseif (is_numeric($value) && 'disable_jevix' == $key || 'process_tags' == $key) {
						$properties[$key] = boolval(intval($value));
					}
				}
			}
		}

		return $properties;
	}

	/**
	 * @param string $k
	 * @param null   $v
	 * @param string $vType
	 *
	 * @return bool
	 */
	public function set($k, $v = null, $vType = '')
	{
		if (is_string($k) && 'createdby' == $k && empty($this->_oldAuthor)) {
			$this->_oldAuthor = parent::get('createdby');
		}

		return parent::set($k, $v, $vType);
	}

	/**
	 * @param null $cacheFlag
	 *
	 * @return bool
	 */
	public function save($cacheFlag = null)
	{
		$isNew      = $this->isNew();
		$action     = $isNew || $this->isDirty('deleted') || $this->isDirty('published');
		$enabled    = $this->get('published') && !$this->get('deleted');
		$new_parent = $this->isDirty('parent');
		$new_author = $this->isDirty('createdby');
		if ($new_parent || $this->isDirty('alias') || $this->isDirty('published') || ($this->get('uri_override') && !$this->get('uri'))) {
			$this->setUri($this->get('alias'));
		}
		$save = parent::save();

		/** @var TicketAuthor $profile */
		if ($new_author && $profile = $this->xpdo->getObject(TicketAuthor::class, $this->_oldAuthor)) {
			$profile->removeAction('ticket', $this->id, $this->get('createdby'));
		}
		if ($profile = $this->xpdo->getObject(TicketAuthor::class, $this->get('createdby'))) {
			if (($action || $new_author) && $enabled) {
				$profile->addAction('ticket', $this->id, $this->id, $this->get('createdby'));
			} elseif (!$enabled) {
				$profile->removeAction('ticket', $this->id, $this->get('createdby'));
			}
		}
		if ($new_parent && !$isNew) {
			$this->updateAuthorsActions();
		}

		return $save;
	}

	/**
	 * @return bool
	 */
	public function remove(array $ancestors = [])
	{
		$collection = $this->xpdo->getIterator('TicketThread', ['name' => 'resource-' . $this->id]);
		/** @var TicketThread $item */
		foreach ($collection as $item) {
			$item->remove();
		}

		/** @var TicketAuthor $profile */
		if ($profile = $this->xpdo->getObject(TicketAuthor::class, $this->get('createdby'))) {
			$profile->removeAction('ticket', $this->id, $this->get('createdby'));
		}

		/** @var TicketTotal $total */
		if ($total = $this->xpdo->getObject(TicketTotal::class, ['id' => $this->id, 'class' => 'Ticket'])) {
			$total->remove();
		}
		if ($total = $this->xpdo->getObject(TicketTotal::class, ['id' => $this->parent, 'class' => 'TicketsSection'])) {
			$total->set('children', $total->get('children') - 1);
			$total->save();
		}

		return parent::remove($ancestors);
	}

	/**
	 * Update ratings for authors actions in section.
	 */
	public function updateAuthorsActions()
	{
		if (!$section = $this->getOne('Section')) {
			$section = $this->xpdo->newObject(TicketsSection::class);
		}

		/** @var TicketsSection $section */
		$ratings = $section->getProperties('ratings');
		$table   = $this->xpdo->getTableName('TicketAuthorAction');
		foreach ($ratings as $action => $rating) {
			$sql = "
                UPDATE {$table} SET `rating` = `multiplier` * {$rating}, `section` = {$section->id}
                WHERE `ticket` = {$this->id} AND `action` = '{$action}';
            ";
			$this->xpdo->exec($sql);
		}

		$c = $this->xpdo->newQuery(TicketAuthorAction::class, ['ticket' => $this->id]);
		$c->select('DISTINCT(owner)');
		$owners = [];
		if ($c->prepare() && $c->stmt->execute()) {
			$owners = $c->stmt->fetchAll(PDO::FETCH_COLUMN);
		}

		$authors = $this->xpdo->getIterator(TicketAuthor::class, ['id:IN' => $owners]);
		/** @var TicketAuthor $author */
		foreach ($authors as $author) {
			$author->updateTotals();
		}
	}

	/**
	 * Returns array with all neighborhood tickets.
	 *
	 * @return array $arr Array with neighborhood from left and right
	 */
	public function getNeighborhood()
	{
		$arr = [];
		$q   = $this->xpdo->newQuery(Ticket::class, ['parent' => $this->parent, 'class_key' => Ticket::class]);
		$q->sortby('id', 'ASC');
		$q->select('id');
		if ($q->prepare() && $q->stmt->execute()) {
			$ids     = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
			$current = array_search($this->get('id'), $ids, true);
			$right   = $left = [];
			foreach ($ids as $k => $v) {
				if ($k > $current) {
					$right[] = $v;
				} elseif ($k < $current) {
					$left[] = $v;
				}
			}
			$arr = [
				'left'  => array_reverse($left),
				'right' => $right,
			];
		}

		return $arr;
	}

	/**
	 * @param string $context
	 */
	public function clearCache($context = '')
	{
		if (!$context) {
			$context = $this->get('context_key');
		}
		parent::clearCache($context);
	}

	public function sanitizeText($text = null, $replaceTags = true)
	{
		return tickets_service()->sanitizeText($text, $replaceTags);
	}
}
