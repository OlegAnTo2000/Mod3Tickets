<?php

namespace Tickets\Processors\Mgr\Comment;

use MODX\Revolution\Processors\Model\UpdateProcessor;

class TicketCommentUnpublishProcessor extends UpdateProcessor
{
	/** @var TicketComment */
	public $object;
	public $objectType = 'Tickets\Model\TicketComment';
	public $classKey = 'Tickets\Model\TicketComment';
	public $languageTopics = ['tickets:default'];
	public $beforeSaveEvent = 'OnBeforeCommentUnpublish';
	public $afterSaveEvent = 'OnCommentUnpublish';
	public $permission = 'comment_publish';
	protected $_sendEmails = false;

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
		$this->object->set('published', 0);

		return parent::beforeSave();
	}

	/**
	 * @return bool
	 */
	public function afterSave()
	{
		$this->object->clearTicketCache();
		/** @var TicketThread $thread */
		if ($thread = $this->object->getOne('Tickets\Model\TicketThread')) {
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
		if ($thread = $this->object->getOne('Tickets\Model\TicketThread')) {
			/** @var Tickets $Tickets */
			if ($Tickets = $this->modx->getService('Tickets')) {
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
			$this->objectType . '_unpublish',
			$this->classKey,
			$this->object->get($this->primaryKeyField)
		);
	}
}

return 'TicketCommentUnpublishProcessor';
