<?php

namespace Tickets;

use \PDO;
use \xPDO\xPDO;
use xPDO\Om\xPDOObject;
use \Tickets\Ticket;
use \Tickets\TicketTotal;
use \Tickets\TicketAuthor;
use \Tickets\TicketsSection;

class TicketAuthorAction extends xPDOObject
{

    /**
     * @param null $cacheFlag
     *
     * @return bool
     */
    public function save($cacheFlag = null)
    {
        $time = time();
        $this->set('createdon', $time);
        $this->set('year', date('Y', $time));
        $this->set('month', date('m', $time));
        $this->set('day', date('d', $time));

        return parent::save($cacheFlag);
    }

}