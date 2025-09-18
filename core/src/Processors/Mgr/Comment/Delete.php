<?php

namespace Tickets\Processors\Mgr\Comment;

use Tickets\Model\TicketComment;
use Tickets\Model\TicketThread;
use MODX\Revolution\Processors\Model\UpdateProcessor;

use function time;

class Delete extends UpdateProcessor
{
	/** @var TicketComment */
	public $object;
	public $objectType      = TicketComment::class;
	public $classKey        = TicketComment::class;
	public $languageTopics  = ['tickets:default'];
	public $beforeSaveEvent = 'OnBeforeCommentDelete';
	public $afterSaveEvent  = 'OnCommentDelete';
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
			'deleted'   => 1,
			'deletedon' => time(),
			'deletedby' => $this->modx->user->get('id'),
		]);

		return parent::beforeSave();
	}

	/**
	 * @return bool
	 */
	public function afterSave()
	{
		$this->object->clearTicketCache();
		if ($thread = $this->object->getOne('Thread')) {
			/** @var TicketThread $thread */
			$thread->updateLastComment();
		}
		$this->modx->cacheManager->delete('tickets/latest.comments');
		$this->modx->cacheManager->delete('tickets/latest.tickets');

		return parent::afterSave();
	}

	public function logManagerAction()
	{
		$this->modx->logManagerAction(
			$this->objectType . '_delete',
			$this->classKey,
			$this->object->get($this->primaryKeyField)
		);
	}
}
