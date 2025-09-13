<?php

namespace Tickets\Processors\Web\Ticket;

use function explode;
use function is_array;

use MODX\Revolution\Processors\Model\GetListProcessor;
use Tickets\Model\Ticket;
use xPDO\Om\xPDOQuery;

class GetList extends GetListProcessor
{
	public $objectType = Ticket::class;
	public $classKey = Ticket::class;
	public $languageTopics = ['tickets:default'];
	public $defaultSortField = 'createdon';
	public $defaultSortDirection = 'DESC';

	/**
	 * @return xPDOQuery
	 */
	public function prepareQueryBeforeCount(xPDOQuery $c)
	{
		if ($parents = $this->getProperty('parents')) {
			if (!is_array($parents)) {
				$parents = explode(',', $parents);
			}
			$c->where(['parent:IN' => $parents]);
		}

		$c->where([
			'class_key' => Ticket::class,
			'published' => 1,
			'deleted' => 0,
		]);

		return $c;
	}
}
