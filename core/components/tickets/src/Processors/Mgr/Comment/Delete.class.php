<?php

use \MODX\Revolution\modX;
use \MODX\Revolution\Processors\Model\UpdateProcessor;
use \xPDO\Om\xPDOQuery;
use \xPDO\Om\xPDOObject;

class TicketCommentDeleteProcessor extends UpdateProcessor
{
    /** @var TicketComment $object */
    public $object;
    public $objectType = 'Tickets\Model\TicketComment';
    public $classKey = 'Tickets\Model\TicketComment';
    public $languageTopics = array('tickets:default');
    public $beforeSaveEvent = 'OnBeforeCommentDelete';
    public $afterSaveEvent = 'OnCommentDelete';
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
            'deleted' => 1,
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
        /** @var TicketThread $thread */
        if ($thread = $this->object->getOne('Thread')) {
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

return 'TicketCommentDeleteProcessor';