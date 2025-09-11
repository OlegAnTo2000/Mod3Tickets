<?php

namespace Tickets\Processors\Web\Ticket;

use MODX\Revolution\Processors\Resource\Delete as ResourceDeleteProcessor;
use MODX\Revolution\modResource;
use Tickets\Ticket;

class Delete extends ResourceDeleteProcessor
{
    public $classKey = Ticket::class;
    /** @var modResource $resource */
    public $resource;
    public $permission = 'ticket_delete';

    public function checkPermissions() {
        $id = $this->getProperty('id', false);
        $this->resource = $this->modx->getObject(modResource::class, $id);
        if (empty($this->resource)) return $this->modx->lexicon('resource_err_nfs',array('id' => $id));
        /* resource owner is this user? */
        if ($this->resource->get('createdby') != $this->modx->user->id) {
            return false;
        }
        return true;
    }
}