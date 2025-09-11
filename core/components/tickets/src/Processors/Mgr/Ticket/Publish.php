<?php

namespace Tickets\Processors\Mgr\Ticket;

use Tickets\Tickets;
use MODX\Revolution\Processors\Resource\Publish as ResourcePublish;

class Publish extends ResourcePublish
{
    public $permission = 'ticket_publish';

    public function fireAfterPublish() {
        parent::fireAfterPublish();
        $this->sendTicketMails();
    }

    /**
     * Call method for notify users about publish ticket
     */
    protected function sendTicketMails()
    {
        /** @var Tickets $Tickets */
        if ($Tickets = $this->modx->services->get('Tickets')) {
            $Tickets->config['tplTicketEmailBcc']          = 'tpl.Tickets.ticket.email.bcc';
            $Tickets->config['tplTicketEmailSubscription'] = 'tpl.Tickets.ticket.email.subscription';
            $Tickets->config['tplAuthorEmailSubscription'] = 'tpl.Tickets.author.email.subscription';
            $Tickets->sendTicketMails($this->resource->toArray(),true);
        }
    }
}
