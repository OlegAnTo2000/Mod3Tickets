<?php

namespace Tickets\Processors\Mgr\Ticket;

use MODX\Revolution\Processors\Resource\Delete as ResourceDeleteProcessor;
use Tickets\Model\Ticket;

class TicketDeleteProcessor extends ResourceDeleteProcessor
{
	public $classKey = Ticket::class;
	public $permission = 'ticket_delete';
}
