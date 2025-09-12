<?php

namespace Tickets\Model;

use \PDO;
use \xPDO\xPDO;
use xPDO\Om\xPDOObject;
use \Tickets\Model\Ticket;
use \Tickets\Model\TicketTotal;
use \Tickets\Model\TicketAuthor;
use \Tickets\Model\TicketsSection;

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