<?php

namespace Tickets\Model;

use Tickets\Model\Ticket;
use Tickets\Model\TicketAuthor;
use Tickets\Model\TicketComment;
use Tickets\Model\TicketThread;
use xPDO\Om\xPDOObject;

/**
 * @property int $id
 */
class TicketVote extends xPDOObject
{
	/**
	 * @param null $cacheFlag
	 *
	 * @return bool
	 */
	public function save($cacheFlag = null)
	{
		$new   = $this->isNew();
		$class = $this->get('class');
		$save  = parent::save($cacheFlag);
		if ($new) {
			$type      = '';
			$ticket_id = 0;
			if (TicketComment::class == $class) {
				$type = 'vote_comment';
				/** @var TicketComment $comment */
				if ($comment = $this->xpdo->getObject(TicketComment::class, $this->id)) {
					/** @var TicketThread $comment */
					if ($thread = $comment->getOne('Thread')) {
						$ticket_id = $thread->get('resource');
					}
				}
			} elseif (Ticket::class == $class) {
				$type      = 'vote_ticket';
				$ticket_id = $this->id;
			}
			if (!empty($type) && !empty($ticket_id)) {
				$multiplier = $this->get('value');
				/** @var TicketAuthor $profile */
				if ($profile = $this->xpdo->getObject(TicketAuthor::class, $this->get('owner'))) {
					$profile->addAction($type, $this->id, $ticket_id, $this->get('createdby'), $multiplier);
				}
			}
		}

		return $save;
	}

	/**
	 * @return bool
	 */
	public function remove(array $ancestors = [])
	{
		$type  = '';
		$class = $this->get('class');
		if (TicketComment::class == $class) {
			$type = 'vote_comment';
		} elseif (Ticket::class == $class) {
			$type = 'vote_ticket';
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
