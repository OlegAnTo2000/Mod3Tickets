<?php

namespace Tickets\Processors\Mgr\Section;

use PDO;
use xPDO\Om\xPDOQuery;
use MODX\Revolution\modResource;
use Tickets\Model\TicketsSection;
use MODX\Revolution\modAccessibleObject;
use MODX\Revolution\Processors\Model\GetListProcessor;

class GetList extends GetListProcessor
{
	public $classKey             = TicketsSection::class;
	public $defaultSortField     = 'pagetitle';
	public $defaultSortDirection = 'ASC';
	public $checkListPermission  = true;
	public $item_id              = 0;

	/**
	 * @return bool
	 */
	public function initialize()
	{
		if ($this->getProperty('combo') && !$this->getProperty('limit') && $id = $this->getProperty('id')) {
			$this->item_id = $id;
		}
		$this->setDefaultProperties([
			'start' => 0,
			'limit' => 20,
			'sort'  => $this->defaultSortField,
			'dir'   => $this->defaultSortDirection,
			'combo' => false,
			'query' => '',
		]);

		return true;
	}

	/**
	 * @return array|string
	 */
	public function process()
	{
		$beforeQuery = $this->beforeQuery();
		if (true !== $beforeQuery) {
			return $this->failure($beforeQuery);
		}
		$data = $this->getData();
		$list = $this->iterate($data);

		return $this->outputArray($list, $data['total']);
	}

	/**
	 * Get the data of the query.
	 *
	 * @return array
	 */
	public function getData()
	{
		$data  = [];
		$limit = \intval($this->getProperty('limit'));
		$start = \intval($this->getProperty('start'));

		// query for chunks
		$c             = $this->modx->newQuery($this->classKey);
		$c             = $this->prepareQueryBeforeCount($c);
		$data['total'] = $this->modx->getCount($this->classKey, $c);
		$c             = $this->prepareQueryAfterCount($c);

		$sortClassKey = $this->getSortClassKey();
		$sortKey      = $this->modx->getSelectColumns(
			$sortClassKey,
			$this->getProperty('sortAlias', $sortClassKey),
			'',
			[$this->getProperty('sort')]
		);
		if (empty($sortKey)) {
			$sortKey = $this->getProperty('sort');
		}
		$c->sortby($sortKey, $this->getProperty('dir'));
		if ($limit > 0) {
			$c->limit($limit, $start);
		}

		if ($c->prepare() && $c->stmt->execute()) {
			$data['results'] = $c->stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		return $data;
	}

	/**
	 * @return xPDOQuery
	 */
	public function prepareQueryBeforeCount(xPDOQuery $c): xPDOQuery
	{
		$c->select('id,parent,pagetitle,context_key');
		$c->where([
			'class_key' => TicketsSection::class,
		]);

		if ($query = $this->getProperty('query')) {
			$c->where(['pagetitle:LIKE' => "%$query%"]);
		} else {
			if ($this->item_id) {
				$c->where(['id' => $this->item_id]);
			}
		}

		return $c;
	}

	/**
	 * Iterate across the data.
	 *
	 * @return array
	 */
	public function iterate(array $data)
	{
		$list               = [];
		$list               = $this->beforeIteration($list);
		$this->currentIndex = 0;
		/** @var xPDOObject|modAccessibleObject $object */
		foreach ($data['results'] as $array) {
			$objectArray = $this->prepareResult($array);
			if (!empty($objectArray) && \is_array($objectArray)) {
				$list[] = $objectArray;
				++$this->currentIndex;
			}
		}
		$list = $this->afterIteration($list);

		return $list;
	}

	/**
	 * @return array
	 */
	public function prepareResult(array $resourceArray)
	{
		$resourceArray['parents'] = [];
		$parents                  = $this->modx->getParentIds(
			$resourceArray['id'],
			2,
			['context' => $resourceArray['context_key']]
		);
		if (0 == $parents[\count($parents) - 1]) {
			unset($parents[\count($parents) - 1]);
		}
		if (!empty($parents) && \is_array($parents)) {
			$q = $this->modx->newQuery(modResource::class, ['id:IN' => $parents]);
			$q->select('id,pagetitle');
			if ($q->prepare() && $q->stmt->execute()) {
				while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
					$key = \array_search($row['id'], $parents, true);
					if (false !== $key) {
						$parents[$key] = $row;
					}
				}
			}
			$resourceArray['parents'] = \array_reverse($parents);
		}

		return $resourceArray;
	}
}
