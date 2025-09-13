<?php

class TicketThreadOpenProcessor extends MODX\Revolution\Processors\Model\UpdateProcessor
{
	/** @var TicketThread */
	public $object;
	public $classKey        = 'TicketThread';
	public $objectType      = 'TicketThread';
	public $languageTopics  = ['tickets:default'];
	public $beforeSaveEvent = 'OnBeforeTicketThreadOpen';
	public $afterSaveEvent  = 'OnTicketThreadOpen';
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
			'closed' => 0,
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
			$this->objectType . '_open',
			$this->classKey,
			$this->object->get($this->primaryKeyField)
		);
	}
}

return 'TicketThreadOpenProcessor';
