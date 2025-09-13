<?php

use Tickets\Model\TicketThread;
use MODX\Revolution\Processors\Model\UpdateProcessor;

class Delete extends UpdateProcessor
{
	/** @var TicketThread */
	public $object;
	public $classKey        = TicketThread::class;
	public $objectType      = TicketThread::class;
	public $languageTopics  = ['tickets:default'];
	public $beforeSaveEvent = 'OnBeforeTicketThreadDelete';
	public $afterSaveEvent  = 'OnTicketThreadDelete';
	public $permission      = 'thread_delete';

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
			'deleted'   => 1,
			'deletedon' => time(),
			'deletedby' => $this->modx->user->get('id'),
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
			$this->objectType . '_delete',
			$this->classKey,
			$this->object->get($this->primaryKeyField)
		);
	}
}
