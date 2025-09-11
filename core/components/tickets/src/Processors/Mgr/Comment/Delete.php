<?php

namespace Tickets\Processors\Mgr\Comment;

use \MODX\Revolution\modX;
use \MODX\Revolution\Processors\Model\UpdateProcessor;
use \xPDO\Om\xPDOQuery;
use \xPDO\Om\xPDOObject;
use Tickets\TicketComment;
use Tickets\TicketThread;

class Delete extends UpdateProcessor
{
    /** @var TicketComment $object */
    public $object;
    public $objectType      = 'Tickets\TicketComment';
    public $classKey        = TicketComment::class;
    public $languageTopics  = array('tickets:default');
    public $beforeSaveEvent = 'OnBeforeCommentDelete';
    public $afterSaveEvent  = 'OnCommentDelete';
    public $permission      = 'comment_delete';


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
            'deleted'   => 1,
            'deletedon' => time(),
            'deletedby' => $this->modx->user->get('id'),
        ));

        return parent::beforeSave();
    }


    /**
     * @return bool
     */
    public function afterSave()
    {
        $this->object->clearTicketCache();
        if ($thread = $this->object->getOne(TicketThread::class)) {
            /** @var TicketThread $thread */
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
        $this->modx->logManagerAction($this->objectType . '_delete', $this->classKey,
            $this->object->get($this->primaryKeyField));
    }

}
