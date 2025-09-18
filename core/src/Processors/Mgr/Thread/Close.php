<?php

namespace Tickets\Processors\Mgr\Thread;

use MODX\Revolution\Processors\Model\UpdateProcessor;
use Tickets\Model\TicketThread;

class Close extends UpdateProcessor
{
	/** @var TicketThread */
	public $object;
	public $classKey        = TicketThread::class;
	public $objectType      = TicketThread::class;
	public $languageTopics  = ['tickets:default'];
	public $beforeSaveEvent = 'OnBeforeTicketThreadClose';
	public $afterSaveEvent  = 'OnTicketThreadClose';
	public $permission      = 'thread_close';

	/**
	 * @return bool
	 */
	public function beforeSet()
	{
		$this->properties = [];

		return true;
	}

	/**
	 * @return bool
	 */
	public function beforeSave()
	{
		$this->object->fromArray([
			'closed' => 1,
		]);

		return parent::beforeSave();
	}

	public function afterSave()
	{
		$this->modx->cacheManager->delete('tickets/latest.comments');
		$this->modx->cacheManager->delete('tickets/latest.tickets');

		return true;
	}

	/**
	 * @param string $action
	 */
	public function logManagerAction($action = '')
	{
		$this->modx->logManagerAction(
			$this->objectType . '_close',
			$this->classKey,
			$this->object->get($this->primaryKeyField)
		);
	}
}
