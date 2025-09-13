<?php

namespace Tickets\Processors\Web\Ticket;

use function date;
use function microtime;

use MODX\Revolution\modResource;
use MODX\Revolution\modUser;
use MODX\Revolution\Processors\Model\CreateProcessor;
use PDO;

use function strtotime;

use Tickets\Model\Ticket;
use Tickets\Model\TicketsSection;
use Tickets\Model\TicketVote;

use function time;

class Vote extends CreateProcessor
{
	/** @var TicketVote */
	public $object;
	public $objectType = 'TicketVote';
	public $classKey = TicketVote::class;
	public $languageTopics = ['tickets:default'];
	public $beforeSaveEvent = 'OnBeforeTicketVote';
	public $afterSaveEvent = 'OnTicketVote';
	public $permission = 'ticket_vote';
	/** @var Ticket|modResource */
	private $ticket;

	/**
	 * @return bool|string|null
	 */
	public function beforeSet()
	{
		$id = (int) $this->getProperty('id');

		if (!$this->modx->user->isAuthenticated($this->modx->context->key)) {
			return $this->modx->lexicon('permission_denied');
		} elseif (!$this->ticket = $this->modx->getObject(modResource::class, $id)) {
			return $this->modx->lexicon('ticket_err_ticket');
		} elseif ($this->ticket->createdby == $this->modx->user->id) {
			return $this->modx->lexicon('ticket_err_vote_own');
		}
		/** @var TicketVote $vote */
		elseif ($this->modx->getCount(
			$this->classKey,
			['id' => $id, 'createdby' => $this->modx->user->id, 'class' => Ticket::class]
		)) {
			return $this->modx->lexicon('ticket_err_vote_already');
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function beforeSave()
	{
		if ($section = $this->ticket->getOne('Section')) {
			/** @var TicketsSection $section */
			$ratings = $section->getProperties('ratings');
			if (isset($ratings['days_ticket_vote']) && '' !== $ratings['days_ticket_vote']) {
				$max = strtotime($this->ticket->get('createdon')) + ((float) $ratings['days_ticket_vote'] * 86400);
				if (time() > $max) {
					return $this->modx->lexicon('ticket_err_vote_ticket_days');
				}
			}
		}

		$this->modx->getRequest();
		$ip = $this->modx->request->getClientIp();

		$value = $this->getProperty('value');
		if ($value > 0) {
			$value = 1;
		} elseif ($value < 0) {
			$value = -1;
		} else {
			$value = 0;
		}

		$this->object->set('id', $this->ticket->id);
		$this->object->set('owner', $this->ticket->createdby);
		$this->object->set('class', Ticket::class);
		$this->object->set('value', $value);
		$this->object->set('ip', $ip['ip']);
		$this->object->set('createdon', date('Y-m-d H:i:s'));
		$this->object->set('createdby', $this->modx->user->id);

		return true;
	}

	/**
	 * @return array|string
	 */
	public function cleanup()
	{
		if ($this->ticket instanceof Ticket) {
			$rating = $this->ticket->getRating();
		} else {
			$rating = ['rating' => 0, 'rating_plus' => 0, 'rating_minus' => 0];

			$q = $this->modx->newQuery(TicketVote::class, ['id' => $this->ticket->id, 'class' => Ticket::class]);
			$q->innerJoin(modUser::class, 'modUser', '`modUser`.`id` = `TicketVote`.`createdby`');
			$q->select('value');
			$tstart = microtime(true);
			if ($q->prepare() && $q->stmt->execute()) {
				$this->modx->startTime += microtime(true) - $tstart;
				++$this->modx->executedQueries;
				$rows = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
				foreach ($rows as $value) {
					$rating['rating'] += $value;
					if ($value > 0) {
						$rating['rating_plus'] += $value;
					} elseif ($value < 0) {
						$rating['rating_minus'] += $value;
					}
				}
				$this->ticket->setProperties($rating, 'tickets', true);
				$this->ticket->save();
			}
		}

		return $this->success('', $rating);
	}
}
