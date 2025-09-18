<?php

namespace Tickets\Processors\Mgr\Comment;

use function tickets_service;

use function array_key_exists;

use Tickets\Model\TicketComment;
use MODX\Revolution\Processors\Model\UpdateProcessor;

class Publish extends UpdateProcessor
{
	/** @var TicketComment */
	public $object;
	public $objectType      = TicketComment::class;
	public $classKey        = TicketComment::class;
	public $languageTopics  = ['tickets:default'];
	public $beforeSaveEvent = 'OnBeforeCommentPublish';
	public $afterSaveEvent  = 'OnCommentPublish';
	public $permission      = 'comment_publish';
	protected $_sendEmails  = false;

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
		$this->object->set('published', 1);
		$properties = $this->object->get('properties');
		if (array_key_exists('was_published', $properties)) {
			unset($properties['was_published']);
			$this->object->set('properties', $properties);
			$this->_sendEmails = true;
		}

		return parent::beforeSave();
	}

	/**
	 * @return bool
	 */
	public function afterSave()
	{
		$this->object->clearTicketCache();
		/** @var TicketThread $thread */
		if ($thread = $this->object->getOne('Thread')) {
			/** @var TicketThread $thread */
			$thread->updateLastComment();
		}

		$this->modx->cacheManager->delete('tickets/latest.comments');
		$this->modx->cacheManager->delete('tickets/latest.tickets');

		if ($this->_sendEmails) {
			$this->sendCommentMails();
		}

		return parent::afterSave();
	}

	protected function sendCommentMails()
	{
		/** @var TicketThread $thread */
		if ($thread = $this->object->getOne('Thread')) {
			/** @var TicketThread $thread */
			/** @var Tickets $Tickets */
			if ($Tickets = tickets_service()) {
				$Tickets->config = $thread->get('properties');
				$Tickets->sendCommentMails($this->object->toArray());
			}
		}
	}

	/**
	 * @param string $action
	 */
	public function logManagerAction($action = '')
	{
		$this->modx->logManagerAction(
			$this->objectType . '_publish',
			$this->classKey,
			$this->object->get($this->primaryKeyField)
		);
	}
}
