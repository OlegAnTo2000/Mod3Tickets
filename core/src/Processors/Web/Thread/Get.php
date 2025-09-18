<?php

namespace Tickets\Processors\Web\Thread;

use function array_slice;
use function count;
use function date;
use function key;

use MODX\Revolution\modUser;
use MODX\Revolution\modUserProfile;
use MODX\Revolution\Processors\Processor;
use PDO;
use Tickets\Model\TicketComment;
use Tickets\Model\TicketThread;

class Get extends Processor
{
	public $classKey       = TicketThread::class;
	public $languageTopics = ['tickets:default'];
	/** @var TicketThread */
	private $object;
	private $comments;
	private $total = 0;

	/**
	 * @return bool
	 */
	public function initialize()
	{
		$thread = $this->getProperty('thread');
		if (!$this->object = $this->modx->getObject($this->classKey, ['name' => $thread])) {
			$this->object = $this->modx->newObject($this->classKey);
			$this->object->fromArray([
				'name'      => $thread,
				'createdby' => $this->modx->user->get('id'),
				'createdon' => date('Y-m-d H:i:s'),
				'resource'  => $this->modx->resource->get('id'),
			]);
			$this->object->save();
		} else {
			if (1 == $this->object->deleted) {
				$this->modx->error->message = $this->modx->lexicon('ticket_thread_err_deleted');

				return false;
			}
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function process()
	{
		$this->getComments();
		$this->checkCommentLast();
		$this->buildTree();

		return $this->cleanup();
	}

	public function getComments()
	{
		$res    = [];
		$result = null;
		$q      = $this->modx->newQuery(TicketComment::class);
		$q->select($this->modx->getSelectColumns(TicketComment::class, 'TicketComment'));
		$q->select($this->modx->getSelectColumns(modUserProfile::class, 'modUserProfile', '', ['id'], true));
		$q->select('`TicketThread`.`resource`');
		$q->select('`modUser`.`username`');
		$q->leftJoin(modUser::class, 'modUser', '`TicketComment`.`createdby` = `modUser`.`id`');
		$q->leftJoin(modUserProfile::class, 'modUserProfile', '`TicketComment`.`createdby` = `modUserProfile`.`internalKey`');
		$q->leftJoin(TicketThread::class, 'TicketThread', '`TicketThread`.`id` = `TicketComment`.`thread`');
		$q->where(['thread' => $this->object->id]);
		$q->sortby('id', 'ASC');
		if ($q->prepare() && $q->stmt->execute()) {
			while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
				$res[$row['id']] = $row;
			}
			$this->total    = count($res);
			$this->comments = $res;
		}
	}

	public function checkCommentLast()
	{
		if (!$this->object->get('comment_last') && $key = key(array_slice($this->comments, -1, 1, true))) {
			$comment = $this->comments[$key];
			$this->object->fromArray([
				'comment_last' => $key,
				'comment_time' => $comment['createdon'],
			]);
			$this->object->save();
		}
	}

	public function buildTree()
	{
		$data           = $this->comments;
		$this->comments = [];
		foreach ($data as $id => &$row) {
			if (empty($row['parent'])) {
				$this->comments[$id] = &$row;
			} else {
				$data[$row['parent']]['children'][$id] = &$row;
			}
		}
	}

	/**
	 * @return string
	 */
	public function cleanup()
	{
		return $this->outputArray($this->comments, $this->total);
	}

	/**
	 * @return array
	 */
	public function getLanguageTopics()
	{
		return $this->languageTopics;
	}
}
