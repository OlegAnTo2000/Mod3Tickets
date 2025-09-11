<?php

namespace Tickets\Processors\Mgr\Comment;

use \MODX\Revolution\modX;
use \MODX\Revolution\Processors\Model\RemoveProcessor;
use \xPDO\Om\xPDOQuery;
use \xPDO\Om\xPDOObject;

class Remove extends RemoveProcessor
{
    /** @var TicketComment $object */
    public $object;
    public $checkRemovePermission = true;
    public $objectType = 'Tickets\TicketComment';
    public $classKey = 'Tickets\TicketComment';
    public $languageTopics = array('tickets');
    public $beforeRemoveEvent = 'OnBeforeCommentRemove';
    public $afterRemoveEvent = 'OnCommentRemove';
    public $permission = 'comment_remove';
    private $children = array();


    /**
     * @return bool|null|string
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
        $children = $this->modx->getIterator('Tickets\TicketComment', array('id:IN' => $this->children));
        /** @var Tickets\TicketComment $child */
        foreach ($children as $child) {
            $child->remove();
        }

        return true;
    }


    /**
     * @param Tickets\TicketComment $parent
     */
    protected function getChildren(Tickets\TicketComment $parent)
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
        $this->object->clearTicketCache();
        /** @var Tickets\TicketThread $thread */
        if ($thread = $this->object->getOne('Tickets\TicketThread')) {
            $thread->updateLastComment();
        }

        $this->modx->cacheManager->delete('tickets/latest.comments');
        $this->modx->cacheManager->delete('tickets/latest.tickets');

        return parent::afterRemove();
    }


    /**
     *
     */
    public function logManagerAction()
    {
        $this->modx->logManagerAction($this->objectType . '_remove', $this->classKey,
            $this->object->get($this->primaryKeyField));
    }

}

return 'TicketCommentRemoveProcessor';