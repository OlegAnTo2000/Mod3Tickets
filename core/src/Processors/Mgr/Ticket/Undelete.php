<?php

namespace Tickets\Processors\Mgr\Ticket;

use MODX\Revolution\Processors\Resource\Undelete as ResourceUndelete;

class Undelete extends ResourceUndelete
{
	public $permission = 'ticket_delete';
}
