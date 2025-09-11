<?php

namespace Tickets\Processors\Web\Ticket;

use \MODX\Revolution\Processors\Resource\Undelete as ResourceUndeleteProcessor;
use \MODX\Revolution\modResource;
use \Tickets\Ticket;

class Undelete extends ResourceUndeleteProcessor
{
	public $classKey = Ticket::class;
    /** @var modResource $resource */
    public $resource;
    public $permission = 'ticket_delete';

    public function checkPermissions() {
        $id = $this->getProperty('id', false);
        $this->resource = $this->modx->getObject(modResource::class, $id);
        if (empty($this->resource)) return false;
        /* resource was deleted by this user? */
        if ($this->resource->get('deletedby') != $this->modx->user->id) {
            return false;
        }
        return true;
    }
}