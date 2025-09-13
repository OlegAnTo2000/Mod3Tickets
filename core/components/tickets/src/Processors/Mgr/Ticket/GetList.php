<?php

use xPDO\Om\xPDOQuery;
use xPDO\Om\xPDOObject;
use Tickets\Model\Ticket;
use MODX\Revolution\modUser;
use Tickets\Model\TicketThread;
use MODX\Revolution\modResource;
use MODX\Revolution\modUserProfile;
use MODX\Revolution\Processors\Model\GetListProcessor;

class GetList extends GetListProcessor
{
	public $classKey = Ticket::class;
	public $defaultSortField = 'id';
	public $defaultSortDirection = 'DESC';

	/**
	 * @return xPDOQuery
	 */
	public function prepareQueryBeforeCount(xPDOQuery $c): xPDOQuery
	{
		$c->leftJoin(modUser::class, 'CreatedBy');
		$c->leftJoin(modUserProfile::class, 'UserProfile', 'UserProfile.internalKey = Ticket.createdby');
		$c->leftJoin(TicketThread::class, 'Thread', 'Thread.resource = Ticket.id');
		$c->select($this->modx->getSelectColumns(Ticket::class, 'Ticket'));
		$c->select([
			'username' => 'CreatedBy.username',
			'author'   => 'UserProfile.fullname',
			'comments' => 'Thread.comments',
		]);
		$c->where([
			'class_key' => Ticket::class,
		]);
		if ($parent = $this->getProperty('parent', 0)) {
			$c->where([
				'parent' => $this->getProperty('parent'),
			]);
		} else {
			$c->leftJoin(modResource::class, 'Parent');
			$c->select([
				'section_id' => 'Parent.id',
				'section' => 'Parent.pagetitle',
			]);
		}
		if ($query = $this->getProperty('query', null)) {
			$c->where([
				'pagetitle:LIKE'               => "%{$query}%",
				'OR:description:LIKE'          => "%{$query}%",
				'OR:introtext:LIKE'            => "%{$query}%",
				'OR:CreatedBy.username:LIKE'   => "%{$query}%",
				'OR:UserProfile.fullname:LIKE' => "%{$query}%",
			]);
		}

		return $c;
	}

	/**
	 * @param xPDOObject $object
	 *
	 * @return array
	 */
	public function prepareRow(xPDOObject $object): array
	{
		$array = parent::prepareRow($object);

		if (empty($array['author'])) {
			$array['author'] = $array['username'];
		}
		$this->modx->getContext($array['context_key']);
		$array['preview_url'] = $this->modx->makeUrl($array['id'], $array['context_key']);

		$array['actions'] = [];
		// View
		if (!empty($array['preview_url'])) {
			$array['actions'][] = [
				'cls'    => '',
				'icon'   => 'icon icon-eye',
				'title'  => $this->modx->lexicon('tickets_action_view'),
				'action' => 'viewTicket',
				'button' => true,
				'menu'   => true,
			];
		}

		// Edit
		$array['actions'][] = [
			'cls'    => '',
			'icon'   => 'icon icon-edit',
			'title'  => $this->modx->lexicon('tickets_action_edit'),
			'action' => 'editTicket',
			'button' => false,
			'menu'   => true,
		];

		// Duplicate
		$array['actions'][] = [
			'cls'    => '',
			'icon'   => 'icon icon-files-o',
			'title'  => $this->modx->lexicon('tickets_action_duplicate'),
			'action' => 'duplicateTicket',
			'button' => false,
			'menu'   => true,
		];

		// Publish
		if (!$array['published']) {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-power-off action-green',
				'title'    => $this->modx->lexicon('tickets_action_publish'),
				'multiple' => $this->modx->lexicon('tickets_action_publish'),
				'action'   => 'publishTicket',
				'button'   => true,
				'menu'     => true,
			];
		} else {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-power-off action-gray',
				'title'    => $this->modx->lexicon('tickets_action_unpublish'),
				'multiple' => $this->modx->lexicon('tickets_action_unpublish'),
				'action'   => 'unpublishTicket',
				'button'   => true,
				'menu'     => true,
			];
		}

		// Delete
		if (!$array['deleted']) {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-trash-o action-red',
				'title'    => $this->modx->lexicon('tickets_action_delete'),
				'multiple' => $this->modx->lexicon('tickets_action_delete'),
				'action'   => 'deleteTicket',
				'button'   => false,
				'menu'     => true,
			];
		} else {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-undo action-green',
				'title'    => $this->modx->lexicon('tickets_action_undelete'),
				'multiple' => $this->modx->lexicon('tickets_action_undelete'),
				'action'   => 'undeleteTicket',
				'button'   => true,
				'menu'     => true,
			];
		}

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

return 'TicketGetListProcessor';
