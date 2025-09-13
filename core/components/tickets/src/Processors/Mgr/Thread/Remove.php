<?php

namespace Tickets\Processors\Mgr\Thread;

use Tickets\Model\TicketThread;
use Tickets\Model\TicketComment;
use MODX\Revolution\Processors\Model\RemoveProcessor;

class Remove extends RemoveProcessor
{
	/** @var TicketThread */
	public $object;
	public $checkRemovePermission = true;
	public $classKey              = TicketThread::class;
	public $objectType            = TicketThread::class;
	public $languageTopics        = ['tickets'];
	public $beforeRemoveEvent     = 'OnBeforeTicketThreadRemove';
	public $afterRemoveEvent      = 'OnTicketThreadRemove';
	public $permission            = 'thread_remove';

	/**
	 * @return bool
	 */
	public function beforeRemove()
	{
		$comments = $this->modx->getIterator(TicketComment::class, ['thread' => $this->object->get('id')]);
		/** @var TicketComment $comment */
		foreach ($comments as $comment) {
			$comment->remove();
		}

		return true;
	}

	/**
	 * @param string $action
	 */
	public function logManagerAction($action = '')
	{
		$this->modx->logManagerAction(
			$this->objectType . '_remove',
			$this->classKey,
			$this->object->get($this->primaryKeyField)
		);
	}

	public function afterRemove()
	{
		$this->modx->cacheManager->delete('tickets/latest.comments');
		$this->modx->cacheManager->delete('tickets/latest.tickets');

		return true;
	}
}
