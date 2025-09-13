<?php

namespace Tickets\Processors\Mgr\Comment;

use function count;

use Tickets\Model\TicketThread;
use Tickets\Model\TicketComment;
use MODX\Revolution\Processors\Model\RemoveProcessor;

class Remove extends RemoveProcessor
{
	public $object;
	public $checkRemovePermission = true;
	public $objectType            = TicketComment::class;
	public $classKey              = TicketComment::class;
	public $languageTopics        = ['tickets'];
	public $beforeRemoveEvent     = 'OnBeforeCommentRemove';
	public $afterRemoveEvent      = 'OnCommentRemove';
	public $permission            = 'comment_remove';
	private $children             = [];

	/**
	 * @return bool|string|null
	 */
	public function initialize()
	{
		$parent = parent::initialize();
		if ($this->checkRemovePermission && !$this->modx->hasPermission($this->permission)) {
			return $this->modx->lexicon('access_denied');
		}

		return $parent;
	}

	/**
	 * @return bool
	 */
	public function beforeRemove()
	{
		$this->getChildren($this->object);
		$children = $this->modx->getIterator(TicketComment::class, ['id:IN' => $this->children]);
		/** @var TicketComment $child */
		foreach ($children as $child) {
			$child->remove();
		}

		return true;
	}

	protected function getChildren(TicketComment $parent)
	{
		$children = $parent->getMany('Children');
		if (count($children) > 0) {
			/** @var TicketComment $child */
			foreach ($children as $child) {
				$this->children[] = $child->get('id');
				$this->getChildren($child);
			}
		}
	}

	/**
	 * @return bool
	 */
	public function afterRemove()
	{
		/** @var TicketComment $comment */
		$comment = $this->object;
		$comment->clearTicketCache();
		/** @var TicketThread $thread */
		if ($thread = $this->object->getOne('Thread')) {
			/** @var TicketThread $thread */
			$thread->updateLastComment();
		}

		$this->modx->cacheManager->delete('tickets/latest.comments');
		$this->modx->cacheManager->delete('tickets/latest.tickets');

		return parent::afterRemove();
	}

	public function logManagerAction()
	{
		$this->modx->logManagerAction(
			$this->objectType . '_remove',
			$this->classKey,
			$this->object->get($this->primaryKeyField)
		);
	}
}
