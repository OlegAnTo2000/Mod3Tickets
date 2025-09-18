<?php

namespace Tickets\Model;

use function is_array;
use function is_object;
use function method_exists;

use MODX\Revolution\modResource;
use PDO;
use Tickets\Model\Ticket;
use Tickets\Model\TicketAuthor;
use Tickets\Model\TicketThread;
use xPDO\Om\xPDOSimpleObject;

/**
 * @property int $id
 */
class TicketComment extends xPDOSimpleObject
{
	public $class_key = self::class;

	/**
	 * @param string $alias
	 * @param null   $criteria
	 * @param bool   $cacheFlag
	 *
	 * @return array
	 */
	public function &getMany($alias, $criteria = null, $cacheFlag = true)
	{
		if ('Attachments' == $alias || 'Votes' == $alias) {
			$criteria = ['class' => $this->class_key];
		}

		return parent::getMany($alias, $criteria, $cacheFlag);
	}

	/**
	 * @param string $alias
	 *
	 * @return bool
	 */
	public function addMany(&$obj, $alias = '')
	{
		$added = false;
		if (is_array($obj)) {
			/** @var \xPDO\Om\xPDOObject $o */
			foreach ($obj as $o) {
				if (is_object($o)) {
					$o->set('class', $this->class_key);
					$added = parent::addMany($obj, $alias);
				}
			}

			return $added;
		} else {
			return parent::addMany($obj, $alias);
		}
	}

	/**
	 * Try to clear cache of ticket.
	 *
	 * @return bool
	 */
	public function clearTicketCache()
	{
		$clear = $this->xpdo->getOption('tickets.clear_cache_on_comment_save');
		if (!empty($clear) && 'false' != $clear) {
			/** @var TicketThread $thread */
			$thread = $this->getOne('Thread');
			/** @var modResource|Ticket $ticket */
			if ($ticket = $this->xpdo->getObject(modResource::class, $thread->get('resource'))) {
				if (method_exists($ticket, 'clearCache')) {
					/** @var modResource|Ticket $ticket */
					$ticket->clearCache();

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Move comment from one thread to another and clear cache of its tickets.
	 *
	 * @param int $from
	 * @param int $to
	 *
	 * @return bool
	 */
	public function changeThread($from, $to)
	{
		/** @var TicketThread $old_thread */
		$old_thread = $this->xpdo->getObject(TicketThread::class, $from);
		/** @var TicketThread $new_thread */
		$new_thread = $this->xpdo->getObject(TicketThread::class, $to);

		if ($new_thread && $old_thread) {
			$this->set('thread', $to);
			$this->save();

			$children = $this->getMany('Children');
			/** @var TicketComment $child */
			foreach ($children as $child) {
				$child->set('parent', $to);
				$child->save();
			}

			$old_thread->updateLastComment();
			/** @var modResource|Ticket $ticket */
			if ($ticket = $this->xpdo->getObject(modResource::class, $old_thread->get('resource'))) {
				if (method_exists($ticket, 'clearCache')) {
					/** @var modResource|Ticket $ticket */
					$ticket->clearCache();
				}
			}

			$new_thread->updateLastComment();
			/** @var modResource|Ticket $ticket */
			if ($ticket = $this->xpdo->getObject(modResource::class, $new_thread->get('resource'))) {
				if (method_exists($ticket, 'clearCache')) {
					/** @var modResource|Ticket $ticket */
					$ticket->clearCache();
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Update comment rating.
	 *
	 * @return array
	 */
	public function updateRating()
	{
		$rating = ['rating' => 0, 'rating_plus' => 0, 'rating_minus' => 0];

		$q = $this->xpdo->newQuery(TicketVote::class, ['id' => $this->id, 'class' => TicketComment::class]);
		$q->select('value');
		if ($q->prepare() && $q->stmt->execute()) {
			while ($value = $q->stmt->fetch(PDO::FETCH_COLUMN)) {
				$rating['rating'] += $value;
				if ($value > 0) {
					$rating['rating_plus'] += $value;
				} else {
					$rating['rating_minus'] += $value;
				}
			}
			$this->fromArray($rating);
			$this->save();
		}

		return $rating;
	}

	/**
	 * @param null $cacheFlag
	 *
	 * @return bool
	 */
	public function save($cacheFlag = null)
	{
		$action     = $this->isNew() || $this->isDirty('deleted') || $this->isDirty('published');
		$enabled    = $this->get('published') && !$this->get('deleted');
		$new_parent = $this->isDirty('thread');
		$save       = parent::save($cacheFlag);

		/** @var TicketThread $thread */
		$thread = $this->getOne('Thread');

		/** @var TicketAuthor $profile */
		if ($profile = $this->xpdo->getObject(TicketAuthor::class, $this->get('createdby'))) {
			if ($action && $enabled) {
				$profile->addAction('comment', $this->id, $thread->get('resource'), $this->get('createdby'));
			} elseif (!$enabled) {
				$profile->removeAction('comment', $this->id, $this->get('createdby'));
			} elseif ($new_parent) {
				$profile->removeAction('comment', $this->id, $this->get('createdby'));
				$profile->addAction('comment', $this->id, $thread->get('resource'), $this->get('createdby'));
			}
		}

		return $save;
	}

	/**
	 * @return bool
	 */
	public function remove(array $ancestors = [])
	{
		$collection = $this->xpdo->getIterator(TicketComment::class, ['parent' => $this->id]);
		/** @var TicketComment $item */
		foreach ($collection as $item) {
			$item->remove();
		}

		/** @var TicketAuthor $profile */
		if ($profile = $this->xpdo->getObject(TicketAuthor::class, $this->get('createdby'))) {
			$profile->removeAction('comment', $this->id, $this->get('createdby'));
		}

		return parent::remove($ancestors);
	}
}
