<?php

namespace Tickets\Processors\Mgr\Comment;

use \MODX\Revolution\modX;
use \MODX\Revolution\Processors\Model\UpdateProcessor;
use \xPDO\Om\xPDOQuery;
use \xPDO\Om\xPDOObject;

class TicketCommentUndeleteProcessor extends UpdateProcessor
{
	/** @var TicketComment $object */
	public $object;
	public $objectType = 'Tickets\Model\TicketComment';
	public $classKey = 'Tickets\Model\TicketComment';
	public $languageTopics = array('tickets:default');
	public $beforeSaveEvent = 'OnBeforeCommentUndelete';
	public $afterSaveEvent = 'OnCommentUndelete';
	public $permission = 'comment_delete';


	/**
	 *
	 */
	public function beforeSet()
	{
		$this->properties = array();

		return true;
	}


	/**
	 * @return bool|null|string
	 */
	public function beforeSave()
	{
		$this->object->fromArray(array(
			'deleted' => 0,
			'deletedon' => null,
			'deletedby' => 0,
		));

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


	/**
	 *
	 */
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
