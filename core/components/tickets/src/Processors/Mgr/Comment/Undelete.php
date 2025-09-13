<?php

namespace Tickets\Processors\Mgr\Comment;

use MODX\Revolution\Processors\Model\UpdateProcessor;

class TicketCommentUndeleteProcessor extends UpdateProcessor
{
	/** @var TicketComment */
	public $object;
	public $objectType      = 'Tickets\Model\TicketComment';
	public $classKey        = 'Tickets\Model\TicketComment';
	public $languageTopics  = ['tickets:default'];
	public $beforeSaveEvent = 'OnBeforeCommentUndelete';
	public $afterSaveEvent  = 'OnCommentUndelete';
	public $permission      = 'comment_delete';

	public function beforeSet()
	{
		$this->properties = [];

		return true;
	}

	/**
	 * @return bool|string|null
	 */
	public function beforeSave()
	{
		$this->object->fromArray([
			'deleted'   => 0,
			'deletedon' => null,
			'deletedby' => 0,
		]);

		return parent::beforeSave();
	}

	/**
	 * @return bool
	 */
	public function afterSave()
	{
		$this->object->clearTicketCache();
		/** @var Tickets\Model\TicketThread $thread */
		if ($thread = $this->object->getOne('Tickets\Model\TicketThread')) {
			$thread->updateLastComment();
		}
		$this->modx->cacheManager->delete('tickets/latest.comments');
		$this->modx->cacheManager->delete('tickets/latest.tickets');

		return parent::afterSave();
	}

	public function logManagerAction()
	{
		$this->modx->logManagerAction(
			$this->objectType . '_undelete',
			$this->classKey,
			$this->object->get($this->primaryKeyField)
		);
	}
}

return 'TicketCommentUndeleteProcessor';
