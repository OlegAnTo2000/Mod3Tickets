<?php

namespace Tickets\Processors\Mgr\Thread;

use xPDO\Om\xPDOQuery;
use xPDO\Om\xPDOObject;
use Tickets\Model\Ticket;
use Tickets\Model\TicketThread;
use Tickets\Model\TicketComment;
use MODX\Revolution\Processors\Model\GetListProcessor;

class GetList extends GetListProcessor
{
	public $objectType           = TicketThread::class;
	public $classKey             = TicketThread::class;
	public $languageTopics       = ['tickets:default'];
	public $defaultSortField     = 'createdon';
	public $defaultSortDirection = 'DESC';

	public function prepareQueryBeforeCount(xPDOQuery $c): xPDOQuery
	{
		$c->leftJoin(Ticket::class, 'Ticket');
		$c->select($this->modx->getSelectColumns(TicketThread::class, 'TicketThread'));
		$c->select([
			'Ticket.pagetitle',
		]);
		if (!$this->getProperty('combo')) {
			$c->leftJoin(TicketComment::class, 'Comments');
			$c->select([
				'comments' => 'COUNT(Comments.id)',
			]);
			$c->groupby('TicketThread.id');
		}
		if ($query = $this->getProperty('query', null)) {
			$query = \trim($query);
			if (\is_numeric($query)) {
				$c->where([
					'TicketThread.id:='          => $query,
					'OR:TicketThread.resource:=' => $query,
				]);
			} else {
				$c->where([
					'Ticket.pagetitle:LIKE'     => "%{$query}%",
					'OR:TicketThread.name:LIKE' => "%{$query}%",
				]);
			}
		}

		return $c;
	}

	/**
	 * Prepare the row for iteration.
	 */
	public function prepareRow(xPDOObject $object): array
	{
		if ($this->getProperty('combo')) {
			return $object->get([
				'id',
				'name',
				'pagetitle',
			]);
		}
		$array = parent::prepareRow($object);

		$array['actions'] = [];

		// View
		$array['actions'][] = [
			'cls'    => '',
			'icon'   => 'icon icon-comments-o',
			'title'  => $this->modx->lexicon('tickets_action_view'),
			'action' => 'viewThread',
			'button' => empty($array['deleted']) || empty($array['closed']),
			'menu'   => true,
		];

		// Publish
		if (!$array['closed']) {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-power-off action-gray',
				'title'    => $this->modx->lexicon('tickets_action_close'),
				'multiple' => $this->modx->lexicon('tickets_action_close'),
				'action'   => 'closeThread',
				'button'   => empty($array['deleted']),
				'menu'     => true,
			];
		} else {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-power-off action-green',
				'title'    => $this->modx->lexicon('tickets_action_open'),
				'multiple' => $this->modx->lexicon('tickets_action_open'),
				'action'   => 'openThread',
				'button'   => true,
				'menu'     => true,
			];
		}

		// Delete
		if (!$array['deleted']) {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-trash-o action-yellow',
				'title'    => $this->modx->lexicon('tickets_action_delete'),
				'multiple' => $this->modx->lexicon('tickets_action_delete'),
				'action'   => 'deleteThread',
				'button'   => false,
				'menu'     => true,
			];
		} else {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-undo action-green',
				'title'    => $this->modx->lexicon('tickets_action_undelete'),
				'multiple' => $this->modx->lexicon('tickets_action_undelete'),
				'action'   => 'undeleteThread',
				'button'   => true,
				'menu'     => true,
			];
		}

		$array['actions'][] = [
			'cls'      => '',
			'icon'     => 'icon icon-trash-o action-red',
			'title'    => $this->modx->lexicon('tickets_action_remove'),
			'multiple' => $this->modx->lexicon('tickets_action_remove'),
			'action'   => 'removeThread',
			'button'   => false,
			'menu'     => true,
		];

		// Menu
		$array['actions'][] = [
			'cls'    => '',
			'icon'   => 'icon icon-cog actions-menu',
			'menu'   => false,
			'button' => true,
			'action' => 'showMenu',
			'type'   => 'menu',
		];

		return $array;
	}
}
