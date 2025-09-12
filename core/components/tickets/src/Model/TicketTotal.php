<?php

namespace Tickets\Model;

use \PDO;
use \xPDO\xPDO;
use \Tickets\Model\Ticket;
use \Tickets\Model\TicketStar;
use \Tickets\Model\TicketComment;
use \Tickets\Model\TicketsSection;

class TicketTotal extends \xPDO\Om\xPDOObject
{
    /**
     * @param null $cacheFlag
     *
     * @return bool
     */
    public function save($cacheFlag = null)
    {
        if ($this->isNew()) {
            $this->fromArray($this->fetchValues(), '', false, true);
        }

        return parent::save($cacheFlag);
    }


    /**
     * Get values from database
     */
    public function fetchValues()
    {
        $values = array();

        $id = $this->get('id');
        $class = $this->get('class');
        switch ($class) {
            case Ticket::class:
                /** @var Ticket $ticket */
                if ($ticket = $this->xpdo->getObject(Ticket::class, $id)) {
                    $rating = $ticket->getRating();
                    $values = array(
                        'comments'     => $ticket->getCommentsCount(),
                        'views'        => $ticket->getViewsCount(),
                        'stars'        => $ticket->getStarsCount(),
                        'rating'       => $rating['rating'],
                        'rating_plus'  => $rating['rating_plus'],
                        'rating_minus' => $rating['rating_minus'],
                    );
                }
                break;
            case TicketComment::class:
                if ($comment = $this->xpdo->getObject(TicketComment::class, $id)) {
                    $values = array(
                        'stars' => $this->xpdo->getCount(TicketStar::class, array('id' => $id, 'class' => TicketComment::class)),
                        'rating' => $comment->get('rating'),
                    );
                }
                break;
            case 'TicketsSection':
                /** @var TicketsSection $section */
                if ($section = $this->xpdo->getObject('TicketsSection', $id)) {
                    $rating = $section->getRating();
                    $values = array(
                        'tickets' => $section->getTicketsCount(),
                        'comments' => $section->getCommentsCount(),
                        'views' => $section->getViewsCount(),
                        'stars' => $section->getStarsCount(),
                        'rating' => $rating['rating'],
                        'rating_plus' => $rating['rating_plus'],
                        'rating_minus' => $rating['rating_minus'],
                    );
                }
                break;
        }
        $this->fromArray($values);

        return $values;
    }

}