<?php

namespace Tickets\Model;

use function array_keys;
use function array_search;
use function array_values;
use function count;
use function date;
use function floatval;
use function implode;
use function is_array;
use function is_null;

use PDO;

use function str_repeat;
use function strpos;
use function strtotime;
use function substr;
use function time;

use xPDO\Om\xPDOObject;

/**
 * @property int $id
 * @property string createdon
 */
class TicketAuthor extends xPDOObject
{
	protected $_ratings = [];

	/**
	 * @param int $multiplier
	 *
	 * @return bool
	 */
	public function addAction($type, $id, $ticket_id, $createdby, $multiplier = 1)
	{
		/** @var Ticket $ticket */
		$ticket = $this->xpdo->getObject('modResource', ['id' => $ticket_id]);
		if (!$ticket || !($ticket instanceof Ticket) || empty($type)) {
			return false;
		}
		/** @var TicketsSection $section */
		$section = $ticket->getOne('Section');
		if (!$section || !($section instanceof TicketsSection)) {
			return false;
		}

		$rating = 0;
		$ratings = $section->getProperties('ratings');
		if (isset($ratings[$type])) {
			$rating = $ratings[$type] * $multiplier;
			/** @noinspection PhpUndefinedFieldInspection */
			$key = [
				'id' => $id,
				'action' => $type,
				'owner' => $this->get('id'),
				'createdby' => $createdby,
			];
			/** @var TicketAuthorAction $action */
			if (!$action = $this->xpdo->getObject('TicketAuthorAction', $key)) {
				$action = $this->xpdo->newObject('TicketAuthorAction');
				$action->fromArray($key, '', true, true);
				$action->fromArray([
					'rating' => $rating,
					'multiplier' => $multiplier,
					'ticket' => $ticket->get('id'),
					'section' => $section->get('id'),
				]);
				if ($action->save()) {
					if (!empty($rating)) {
						$this->set('rating', $this->get('rating') + $rating);
					}
					if ($field = $this->_getTotalField($type)) {
						if (0 === strpos($type, 'vote_')) {
							$this->set($field, $this->get($field) + $rating);
							if ($rating > 0) {
								$field .= '_up';
								$this->set($field, $this->get($field) + 1);
							} elseif ($rating < 0) {
								$field .= '_down';
								$this->set($field, $this->get($field) + 1);
							}
						} else {
							$this->set($field, $this->get($field) + 1);
						}
					}
					$this->save();
				} else {
					return false;
				}
			}
		}
		$this->_updateTotal($type, $ticket, $section, $rating);

		return true;
	}

	/**
	 * @param int $id
	 * @param int $createdby
	 *
	 * @return bool
	 */
	public function removeAction($type, $id = 0, $createdby = 0)
	{
		$key = [
			'id' => $id,
			'action' => $type,
			'owner' => $this->get('id'),
			'createdby' => $createdby,
		];
		/** @var TicketAuthorAction $action */
		if ($action = $this->xpdo->getObject('TicketAuthorAction', $key)) {
			if ($section = $action->getOne('Section')) {
				if ($ticket = $action->getOne('Ticket')) {
					$this->_updateTotal($type, $ticket, $section, 0, true);
				}
			}

			$rating = $action->get('rating');
			if ($action->remove()) {
				if (!empty($rating)) {
					$this->set('rating', $this->get('rating') - $rating);
				}
				if (!empty($rating) && $field = $this->_getTotalField($type)) {
					if (0 === strpos($type, 'vote_')) {
						$this->set($field, $this->get($field) - $rating);
						$field .= $rating > 0
							? '_up'
							: '_down';
					}
					$this->set($field, $this->get($field) - 1);
				}
				$this->save();
			} else {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param bool $clearActions
	 * @param bool $updateTotals
	 *
	 * @return $this
	 */
	public function refreshActions($clearActions = true, $updateTotals = true)
	{
		$this->updateTickets($clearActions);
		$this->updateComments($clearActions);
		$this->updateViews($clearActions);
		$this->updateStars($clearActions);
		$this->updateVotes($clearActions);
		if ($updateTotals) {
			$this->updateTotals();

			$classes = ['TicketsSection', 'Ticket'];
			foreach ($classes as $class) {
				$c = $this->xpdo->newQuery($class, ['createdby' => $this->id]);
				if ('TicketComment' != $class) {
					$c->where(['class_key' => $class]);
				}
				$c->leftJoin('TicketTotal', 'Total');
				$c->select($class . '.id');
				$c->select($this->xpdo->getSelectColumns('TicketTotal', 'Total', '', ['id'], true));
				if ($c->prepare() && $c->stmt->execute()) {
					while ($row = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
						/** @var TicketTotal $total */
						$total = $this->xpdo->newObject('TicketTotal');
						if (is_null($row['class'])) {
							$row['class'] = $class;
						} else {
							$total->_new = false;
						}
						$total->fromArray($row, '', true, true);
						$total->fetchValues();
						$total->save();
					}
				}
			}
		}

		return $this;
	}

	public function updateTickets($clearActions)
	{
		$action = 'ticket';
		if ($clearActions) {
			$this->xpdo->removeCollection('TicketAuthorAction', ['owner' => $this->id, 'action' => $action]);
		}

		$c = $this->xpdo->newQuery('Ticket', [
			'createdby' => $this->id,
			'class_key' => 'Ticket',
			'published' => 1,
			'deleted' => 0,
		]);

		$c->select('id, parent, createdby, createdon');
		if ($c->prepare() && $c->stmt->execute()) {
			while ($row = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
				$ratings = $this->_getRatings($row['parent']);
				if (isset($ratings[$action])) {
					$rating = $ratings[$action];
					$record = [
						'id' => $row['id'],
						'action' => $action,
						'rating' => $rating,
						'ticket' => $row['id'],
						'section' => $row['parent'],
						'createdby' => $row['createdby'],
						'createdon' => date('Y-m-d H:i:s', $row['createdon']),
						'owner' => $row['createdby'],
						'year' => date('Y', $row['createdon']),
						'month' => date('m', $row['createdon']),
						'day' => date('d', $row['createdon']),
					];
					$keys = array_keys($record);
					$fields = '`' . implode('`,`', $keys) . '`';
					$placeholders = substr(str_repeat('?,', count($keys)), 0, -1);
					$sql = "INSERT INTO {$this->xpdo->getTableName('TicketAuthorAction')} ({$fields}) VALUES ({$placeholders});";
					$this->xpdo->prepare($sql)->execute(array_values($record));
				}
			}
		}
	}

	public function updateComments($clearActions)
	{
		$action = 'comment';
		if ($clearActions) {
			$this->xpdo->removeCollection('TicketAuthorAction', ['owner' => $this->id, 'action' => $action]);
		}

		$c = $this->xpdo->newQuery('TicketComment', [
			'createdby' => $this->id,
			'published' => 1,
			'deleted' => 0,
		]);
		$c->innerJoin('TicketThread', 'Thread');
		$c->innerJoin('Ticket', 'Ticket', 'Ticket.id = Thread.resource AND Ticket.class_key = "Ticket"');
		$c->select('
            TicketComment.id, TicketComment.createdby, TicketComment.createdon,
            Ticket.id as ticket, Ticket.parent as section
        ');
		if ($c->prepare() && $c->stmt->execute()) {
			while ($row = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
				$ratings = $this->_getRatings($row['section']);
				if (isset($ratings[$action])) {
					$rating = $ratings[$action];
					$record = [
						'id' => $row['id'],
						'action' => $action,
						'rating' => $rating,
						'ticket' => $row['ticket'],
						'section' => $row['section'],
						'createdby' => $row['createdby'],
						'createdon' => $row['createdon'],
						'owner' => $row['createdby'],
						'year' => date('Y', strtotime($row['createdon'])),
						'month' => date('m', strtotime($row['createdon'])),
						'day' => date('d', strtotime($row['createdon'])),
					];
					$keys = array_keys($record);
					$fields = '`' . implode('`,`', $keys) . '`';
					$placeholders = substr(str_repeat('?,', count($keys)), 0, -1);
					$sql = "INSERT INTO {$this->xpdo->getTableName('TicketAuthorAction')} ({$fields}) VALUES ({$placeholders});";
					$this->xpdo->prepare($sql)->execute(array_values($record));
				}
			}
		}
	}

	public function updateViews($clearActions)
	{
		$action = 'view';
		if ($clearActions) {
			$this->xpdo->removeCollection('TicketAuthorAction', ['owner' => $this->id, 'action' => $action]);
		}

		$c = $this->xpdo->newQuery('TicketView', [
			'uid' => $this->id,
			'Ticket.published' => 1,
			'Ticket.deleted' => 0,
		]);
		$c->innerJoin('Ticket', 'Ticket', 'Ticket.id = TicketView.parent AND Ticket.class_key = "Ticket"');
		$c->select('uid, timestamp, Ticket.id, Ticket.parent as section');
		if ($c->prepare() && $c->stmt->execute()) {
			while ($row = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
				$ratings = $this->_getRatings($row['section']);
				if (isset($ratings[$action])) {
					$rating = $ratings[$action];
					$record = [
						'id' => $row['id'],
						'action' => $action,
						'rating' => $rating,
						'ticket' => $row['id'],
						'section' => $row['section'],
						'createdby' => $row['uid'],
						'createdon' => $row['timestamp'],
						'owner' => $row['uid'],
						'year' => date('Y', strtotime($row['timestamp'])),
						'month' => date('m', strtotime($row['timestamp'])),
						'day' => date('d', strtotime($row['timestamp'])),
					];
					$keys = array_keys($record);
					$fields = '`' . implode('`,`', $keys) . '`';
					$placeholders = substr(str_repeat('?,', count($keys)), 0, -1);
					$sql = "INSERT INTO {$this->xpdo->getTableName('TicketAuthorAction')} ({$fields}) VALUES ({$placeholders});";
					$this->xpdo->prepare($sql)->execute(array_values($record));
				}
			}
		}
	}

	public function updateStars($clearActions)
	{
		$actions = ['star_ticket', 'star_comment'];
		if ($clearActions) {
			$this->xpdo->removeCollection('TicketAuthorAction', ['owner' => $this->id, 'action:IN' => $actions]);
		}

		foreach ($actions as $action) {
			$c = $this->xpdo->newQuery('TicketStar', ['owner' => $this->id]);
			if ('star_ticket' == $action) {
				$c->where(['class' => 'Ticket']);
				$c->innerJoin('Ticket', 'Ticket', 'Ticket.id = TicketStar.id AND Ticket.class_key = "Ticket"');
				$c->select('
                    TicketStar.id, TicketStar.createdon, TicketStar.createdby,
                    Ticket.id as ticket, Ticket.parent as section
                ');
				$c->where([
					'Ticket.published' => 1,
					'Ticket.deleted' => 0,
				]);
			} else {
				$c->where(['class' => 'TicketComment']);
				$c->innerJoin('TicketComment', 'Comment', 'Comment.id = TicketStar.id');
				$c->innerJoin('TicketThread', 'Thread', 'Thread.id = Comment.thread');
				$c->innerJoin('Ticket', 'Ticket', 'Thread.resource = Ticket.id AND Ticket.class_key = "Ticket"');
				$c->select('
                    TicketStar.id, TicketStar.createdon, TicketStar.createdby,
                    Ticket.id as ticket, Ticket.parent as section
                ');
				$c->where([
					'Comment.published' => 1,
					'Comment.deleted' => 0,
				]);
			}

			if ($c->prepare() && $c->stmt->execute()) {
				while ($row = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
					$ratings = $this->_getRatings($row['section']);
					if (isset($ratings[$action]) && $row['createdby'] != $this->id) {
						$rating = $ratings[$action];
						$record = [
							'id' => $row['id'],
							'action' => $action,
							'rating' => $rating,
							'ticket' => $row['ticket'],
							'section' => $row['section'],
							'createdby' => $row['createdby'],
							'createdon' => $row['createdon'],
							'owner' => $this->id,
							'year' => date('Y', strtotime($row['createdon'])),
							'month' => date('m', strtotime($row['createdon'])),
							'day' => date('d', strtotime($row['createdon'])),
						];
						$keys = array_keys($record);
						$fields = '`' . implode('`,`', $keys) . '`';
						$placeholders = substr(str_repeat('?,', count($keys)), 0, -1);
						$sql = "INSERT INTO {$this->xpdo->getTableName('TicketAuthorAction')} ({$fields}) VALUES ({$placeholders});";
						$this->xpdo->prepare($sql)->execute(array_values($record));
					}
				}
			}
		}
	}

	public function updateVotes($clearActions)
	{
		$actions = ['vote_ticket', 'vote_comment'];
		if ($clearActions) {
			$this->xpdo->removeCollection('TicketAuthorAction', ['owner' => $this->id, 'action:IN' => $actions]);
		}

		foreach ($actions as $action) {
			$c = $this->xpdo->newQuery('TicketVote', ['owner' => $this->id]);
			if ('vote_ticket' == $action) {
				$c->where(['class' => 'Ticket']);
				$c->innerJoin('Ticket', 'Ticket', 'Ticket.id = TicketVote.id AND Ticket.class_key = "Ticket"');
				$c->select('
                    TicketVote.id, TicketVote.createdon, TicketVote.createdby, TicketVote.value,
                    Ticket.id as ticket, Ticket.parent as section, Ticket.createdon as ticket_date
                ');
				$c->where([
					'Ticket.published' => 1,
					'Ticket.deleted' => 0,
				]);
			} else {
				$c->where(['class' => 'TicketComment']);
				$c->innerJoin('TicketComment', 'Comment', 'Comment.id = TicketVote.id');
				$c->innerJoin('TicketThread', 'Thread', 'Thread.id = Comment.thread');
				$c->innerJoin('Ticket', 'Ticket', 'Thread.resource = Ticket.id AND Ticket.class_key = "Ticket"');
				$c->select('
                    TicketVote.id, TicketVote.createdon, TicketVote.createdby, TicketVote.value, TicketVote.owner,
                    Ticket.id as ticket, Ticket.parent as section, Ticket.createdon as ticket_date
                ');
				$c->where([
					'Comment.published' => 1,
					'Comment.deleted' => 0,
				]);
			}
			if ($c->prepare() && $c->stmt->execute()) {
				while ($row = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
					$ratings = $this->_getRatings($row['section']);
					if (!isset($ratings[$action])) {
						continue;
					}
					$max = 0;
					$ticket_date = $row['ticket_date'];
					$createdon = strtotime($row['createdon']);
					if ('vote_ticket' == $action) {
						if (isset($ratings['days_ticket_vote']) && '' !== $ratings['days_ticket_vote']) {
							$max = $ticket_date + ((float) $ratings['days_ticket_vote'] * 86400);
						}
					} elseif (isset($ratings['days_comment_vote']) && '' !== $ratings['days_comment_vote']) {
						$max = $ticket_date + ((float) $ratings['days_comment_vote'] * 86400);
					}
					if ($row['createdby'] != $this->id && (!$max || $max > $createdon)) {
						$rating = $ratings[$action] * $row['value'];
						$record = [
							'id' => $row['id'],
							'action' => $action,
							'rating' => $rating,
							'multiplier' => $row['value'],
							'ticket' => $row['ticket'],
							'section' => $row['section'],
							'createdby' => $row['createdby'],
							'createdon' => $row['createdon'],
							'owner' => $this->id,
							'year' => date('Y', $createdon),
							'month' => date('m', $createdon),
							'day' => date('d', $createdon),
						];

						$keys = array_keys($record);
						$fields = '`' . implode('`,`', $keys) . '`';
						$placeholders = substr(str_repeat('?,', count($keys)), 0, -1);
						$sql = "INSERT INTO {$this->xpdo->getTableName('TicketAuthorAction')} ({$fields}) VALUES ({$placeholders});";
						$this->xpdo->prepare($sql)->execute(array_values($record));
					}
				}
			}
		}
	}

	/**
	 * @return bool
	 */
	public function updateTotals()
	{
		$fields = [
			'tickets' => 'ticket',
			'comments' => 'comment',
			'views' => 'view',
			'stars_tickets' => 'star_ticket',
			'stars_comments' => 'star_comment',
		];
		// Simple totals
		foreach ($fields as $field => $action) {
			$c = $this->xpdo->newQuery('TicketAuthorAction', [
				'owner' => $this->id,
				'action' => $action,
			]);
			$c->select('id');
			$count = $this->xpdo->getCount('TicketAuthorAction', $c);
			$this->set($field, $count);
		}
		// Votes
		foreach (['ticket', 'comment'] as $field) {
			foreach (['up', 'down'] as $type) {
				$count = $this->xpdo->getCount('TicketAuthorAction', [
					'owner' => $this->id,
					'rating:' . ('up' == $type ? '>' : '<') => 0,
					'action' => "vote_{$field}",
				]);
				$this->set("votes_{$field}s_{$type}", $count);
			}
		}
		// Votes rating
		foreach (['ticket', 'comment'] as $field) {
			$c = $this->xpdo->newQuery('TicketAuthorAction', [
				'owner' => $this->id,
				'action' => "vote_{$field}",
			]);
			$c->select('SUM(rating)');
			if ($c->prepare() && $c->stmt->execute()) {
				$this->set("votes_{$field}s", floatval($c->stmt->fetchColumn()));
			}
		}
		// Total rating
		$c = $this->xpdo->newQuery('TicketAuthorAction', ['owner' => $this->id]);
		$c->select('SUM(rating)');
		if ($c->prepare() && $c->stmt->execute()) {
			$this->set('rating', floatval($c->stmt->fetchColumn()));
		}

		return $this->save();
	}

	/**
	 * @return string
	 */
	protected function _getTotalField($type)
	{
		switch ($type) {
			case 'ticket':
				$field = 'tickets';
				break;
			case 'comment':
				$field = 'comments';
				break;
			case 'view':
				$field = 'views';
				break;
			case 'vote_ticket':
				$field = 'votes_tickets';
				break;
			case 'vote_comment':
				$field = 'votes_comments';
				break;
			case 'star_ticket':
				$field = 'stars_tickets';
				break;
			case 'star_comment':
				$field = 'stars_comments';
				break;
			default:
				$field = '';
		}

		return $field;
	}

	/**
	 * @return array
	 */
	protected function _getRatings($section_id)
	{
		if (!isset($this->_ratings[$section_id])) {
			/** @var TicketsSection $section */
			if (!$section = $this->xpdo->getObject('TicketsSection', ['id' => $section_id])) {
				$section = $this->xpdo->newObject('TicketsSection');
			}

			$this->_ratings[$section_id] = $section->getProperties('ratings');
		}

		return $this->_ratings[$section_id];
	}

	/**
	 * @param null $cacheFlag
	 *
	 * @return bool
	 */
	public function save($cacheFlag = null)
	{
		$createdon = parent::get('createdon');
		if ((!$createdon || '0000-00-00 00:00:00' == $createdon) && $this->isNew()) {
			$this->set('createdon', time());
		}

		return parent::save($cacheFlag);
	}

	/**
	 * @param string         $type
	 * @param Ticket         $ticket
	 * @param TicketsSection $section
	 * @param int            $rating
	 * @param bool           $remove
	 */
	protected function _updateTotal($type, $ticket, $section, $rating, $remove = false)
	{
		$key = [
			'id' => $section->id,
			'class' => 'TicketsSection',
		];
		if (!$section_total = $this->xpdo->getObject('TicketTotal', $key)) {
			$section_total = $this->xpdo->newObject('TicketTotal');
			$section_total->fromArray($key, '', true, true);
		}

		$key = [
			'id' => $ticket->id,
			'class' => 'Ticket',
		];
		if (!$ticket_total = $this->xpdo->getObject('TicketTotal', $key)) {
			$ticket_total = $this->xpdo->newObject('TicketTotal');
			$ticket_total->fromArray($key, '', true, true);
		}

		switch ($type) {
			case 'ticket':
				if ($remove) {
					$section_total->set('tickets', $section_total->get('tickets') - 1);
					$section_total->save();
					$ticket_total->remove();

					return;
				} else {
					$section_total->set('tickets', $section_total->get('tickets') + 1);
				}
				break;
			case 'view':
				if ($remove) {
					$ticket_total->set('views', $ticket_total->get('views') - 1);
					$section_total->set('views', $section_total->get('views') - 1);
				} else {
					$ticket_total->set('views', $ticket_total->get('views') + 1);
					$section_total->set('views', $section_total->get('views') + 1);
				}
				break;
			case 'comment':
				if ($remove) {
					$ticket_total->set('comments', $ticket_total->get('comments') - 1);
					$section_total->set('comments', $section_total->get('comments') - 1);
				} else {
					$ticket_total->set('comments', $ticket_total->get('comments') + 1);
					$section_total->set('comments', $section_total->get('comments') + 1);
				}
				break;
			case 'vote_ticket':
				if ($remove) {
					$ticket_total->set('rating', $ticket_total->get('rating') - $rating);
					$section_total->set('rating', $section_total->get('rating') - $rating);
					if ($rating > 0) {
						$ticket_total->set('rating_plus', $ticket_total->get('rating_plus') - 1);
						$section_total->set('rating_plus', $section_total->get('rating_plus') - 1);
					} elseif ($rating < 0) {
						$ticket_total->set('rating_minus', $ticket_total->get('rating_minus') - 1);
						$section_total->set('rating_minus', $section_total->get('rating_minus') - 1);
					}
				} else {
					$ticket_total->set('rating', $ticket_total->get('rating') + $rating);
					$section_total->set('rating', $section_total->get('rating') + $rating);
					if ($rating > 0) {
						$ticket_total->set('rating_plus', $ticket_total->get('rating_plus') + 1);
						$section_total->set('rating_plus', $section_total->get('rating_plus') + 1);
					} elseif ($rating < 0) {
						$ticket_total->set('rating_minus', $ticket_total->get('rating_minus') + 1);
						$section_total->set('rating_minus', $section_total->get('rating_minus') + 1);
					}
				}
				break;
			case 'star_ticket':
				if ($remove) {
					$ticket_total->set('stars', $ticket_total->get('stars') - 1);
					$section_total->set('stars', $section_total->get('stars') - 1);
				} else {
					$ticket_total->set('stars', $ticket_total->get('stars') + 1);
					$section_total->set('stars', $section_total->get('stars') + 1);
				}
				break;
		}

		$section_total->save();
		$ticket_total->save();
	}

	/**
	 * @param int $uid
	 *
	 * @return bool
	 */
	public function Subscribe($uid = 0)
	{
		if (!$uid) {
			$uid = $this->xpdo->user->id;
		}

		$properties = $this->get('properties');
		if (empty($properties['subscribers']) || !is_array($properties['subscribers'])) {
			$subscribers = [];
		} else {
			$subscribers = $properties['subscribers'];
		}

		$found = array_search($uid, $subscribers, true);
		if (false === $found) {
			$subscribers[] = $uid;
		} else {
			unset($subscribers[$found]);
		}
		$properties['subscribers'] = $subscribers;
		$this->set('properties', $properties);
		$this->save();

		return false === $found;
	}
}
