<?php

namespace Tickets\Processors\Mgr\Subscribe;

use function intval;
use function is_numeric;
use function trim;

use xPDO\Om\xPDOObject;
use xPDO\Om\xPDOQuery;

class GetList extends \MODX\Revolution\Processors\Model\GetListProcessor
{
	public $objectType           = modUser::class;
	public $classKey             = modUser::class;
	public $languageTopics       = ['tickets:default'];
	public $defaultSortField     = 'modUser.id';
	public $defaultSortDirection = 'DESC';
	public $subscribers          = [];

	public function beforeQuery()
	{
		$target = intval($this->getProperty('parents'));
		if ($section = $this->modx->getObject(TicketsSection::class, $target, false)) {
			$properties = $section->get('properties');
		}

		$this->subscribers = !empty($properties['subscribers'])
			? $properties['subscribers']
			: [];

		return true;
	}

	public function prepareQueryBeforeCount(xPDOQuery $c)
	{
		$c->leftJoin(modUserProfile::class, 'Profile');
		if (empty($this->subscribers)) {
			$c->where([
				'modUser.id' => 0,
			]);
		} else {
			$where = [
				['modUser.id:IN' => $this->subscribers],
			];

			if ($query = $this->getProperty('query', null)) {
				$query = trim($query);
				if (is_numeric($query)) {
					$where[]['modUser.id:='] = $query;
				} else {
					$where[] = [
						'Profile.fullname:LIKE'    => '%' . $query . '%',
						'OR:modUser.username:LIKE' => '%' . $query . '%',
						'OR:Profile.email:LIKE'    => '%' . $query . '%',
					];
				}
			}
			$c->where($where);
		}

		return $c;
	}

	public function prepareQueryAfterCount(xPDOQuery $c)
	{
		$c->select($this->modx->getSelectColumns(modUser::class, 'modUser'));
		$c->select($this->modx->getSelectColumns(modUserProfile::class, 'Profile', '', ['fullname', 'email']));

		return $c;
	}

	/**
	 * Get the data of the query.
	 *
	 * @return array
	 */
	public function getData()
	{
		$data = [];

		$limit = intval($this->getProperty('limit'));
		$start = intval($this->getProperty('start'));

		/* query for chunks */
		$c             = $this->modx->newQuery($this->classKey);
		$c             = $this->prepareQueryBeforeCount($c);
		$data['total'] = $this->modx->getCount($this->classKey, $c);
		$c             = $this->prepareQueryAfterCount($c);

		$sortClassKey = $this->getSortClassKey();
		$sortKey      = $this->modx->getSelectColumns($sortClassKey, $this->getProperty('sortAlias', $sortClassKey), '', [$this->getProperty('sort')]);
		if (empty($sortKey)) {
			$sortKey = $this->getProperty('sort');
		}
		$c->sortby($sortKey, $this->getProperty('dir'));
		if ($limit > 0) {
			$c->limit($limit, $start);
		}

		$data['results'] = $this->modx->getCollection($this->classKey, $c);

		return $data;
	}

	/**
	 * @return array
	 */
	public function prepareRow(xPDOObject $object)
	{
		$array = parent::prepareRow($object);

		unset($array['properties']);

		$array['actions']   = [];
		$array['actions'][] = [
			'cls'      => '',
			'icon'     => 'icon icon-trash-o action-gray',
			'title'    => $this->modx->lexicon('tickets_action_unsubscribe'),
			'multiple' => $this->modx->lexicon('tickets_action_unsubscribe'),
			'action'   => 'unsubscribeSection',
			'button'   => true,
			'menu'     => false,
		];

		return $array;
	}
}

return 'TicketSubscribesGetListProcessor';
