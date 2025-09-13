<?php

namespace Tickets\Processors\Mgr\Ticket;

use MODX\Revolution\Processors\Resource\Unpublish as ResourceUnpublish;

class Unpublish extends ResourceUnpublish
{
	public $permission = 'ticket_publish';
}
