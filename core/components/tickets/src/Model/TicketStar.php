<?php

namespace Tickets\Model;

use \PDO;
use \xPDO\xPDO;
use Tickets\Model\Ticket;
use Tickets\Model\TicketAuthor;
use \Tickets\Model\TicketThread;
use \Tickets\Model\TicketComment;

/**
 * @property int $id
 */
class TicketStar extends \xPDO\Om\xPDOObject
{

    /**
     * @param null $cacheFlag
     *
     * @return bool
     */
    public function save($cacheFlag = null)
    {
        $new = $this->isNew();
        $class = $this->get('class');
        $save = parent::save($cacheFlag);
        if ($new) {
            $type = '';
            $ticket_id = 0;
            if ($class == TicketComment::class) {
                $type = 'star_comment';
                /** @var TicketComment $comment */
                if ($comment = $this->xpdo->getObject(TicketComment::class, $this->id)) {
                    /** @var TicketThread $comment */
                    if ($thread = $comment->getOne('Thread')) {
                        $ticket_id = $thread->get('resource');
                    }
                }
            } elseif ($class == Ticket::class) {
                $type = 'star_ticket';
                $ticket_id = $this->id;
            }
            if (!empty($type) && !empty($ticket_id)) {
                /** @var TicketAuthor $profile */
                if ($profile = $this->xpdo->getObject(TicketAuthor::class, $this->get('owner'))) {
                    $profile->addAction($type, $this->id, $ticket_id, $this->get('createdby'));
                }
            }
        }

        return $save;
    }


    /**
     * @param array $ancestors
     *
     * @return bool
     */
    public function remove(array $ancestors = array())
    {
        $type = '';
        $class = $this->get('class');
        if ($class == TicketComment::class) {
            $type = 'star_comment';
        } elseif ($class == Ticket::class) {
            $type = 'star_ticket';
        }
        if (!empty($type)) {
            /** @var TicketAuthor $profile */
            if ($profile = $this->xpdo->getObject(TicketAuthor::class, $this->get('owner'))) {
                $profile->removeAction($type, $this->id, $this->get('createdby'));
            }
        }

        return parent::remove($ancestors);
    }

}