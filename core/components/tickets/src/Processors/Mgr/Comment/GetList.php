<?php

namespace Tickets\Processors\Mgr\Comment;

use function explode;
use function html_entity_decode;
use function is_array;
use function is_numeric;

use MODX\Revolution\modResource;
use MODX\Revolution\modUser;
use MODX\Revolution\modUserProfile;
use MODX\Revolution\Processors\Model\GetListProcessor;

use function strip_tags;

use Tickets\Model\TicketComment;
use Tickets\Model\TicketThread;

use function trim;

use xPDO\Om\xPDOObject;
use xPDO\Om\xPDOQuery;

class GetList extends GetListProcessor
{
	public $objectType           = TicketComment::class;
	public $classKey             = TicketComment::class;
	public $languageTopics       = ['tickets:default'];
	public $defaultSortField     = 'createdon';
	public $defaultSortDirection = 'DESC';

	/**
	 * @return xPDOQuery
	 */
	public function prepareQueryBeforeCount(xPDOQuery $c): xPDOQuery
	{
		/* Get all comments by section */
		if ($section = (int) $this->getProperty('section')) {
			if ($section = $this->modx->getObject(modResource::class, $section)) {
				$parents = $this->modx->getChildIds(
					$section->get('id'),
					1,
					['context' => $section->get('context_key')]
				);
				if (empty($parents)) {
					$parents = ['0'];
				}
				$c->where(['Thread.resource:IN' => $parents]);
			}
		} /* OR get all comments by threads list */ elseif ($threads = $this->getProperty('threads')) {
			if (!is_array($threads)) {
				$threads = explode(',', $threads);
			}
			if (!empty($threads)) {
				$c->where(['TicketComment.thread:IN' => $threads]);
			}
		} /* OR get all comments by tickets list */ elseif ($parents = $this->getProperty('parents')) {
			if (!is_array($parents)) {
				$parents = explode(',', $parents);
			}
			if (!empty($parents)) {
				$c->where(['Thread.resource:IN' => $parents]);
			}
		} /* OR get all comments */ else {
			// $c->where(array('Thread.resource:!=' => 0));
		}

		if ($query = $this->getProperty('query', null)) {
			$query = trim($query);
			if (is_numeric($query)) {
				$c->where([
					'TicketComment.id:='        => $query,
					'OR:TicketComment.parent:=' => $query,
				]);
			} else {
				$c->where([
					'TicketComment.text:LIKE'     => '%' . $query . '%',
					'OR:TicketComment.raw:LIKE'   => '%' . $query . '%',
					'OR:TicketComment.name:LIKE'  => '%' . $query . '%',
					'OR:TicketComment.email:LIKE' => '%' . $query . '%',
				]);
			}
		}

		$c->leftJoin(TicketThread::class, 'Thread');
		$c->leftJoin(modUser::class, 'User');
		$c->leftJoin(modUserProfile::class, 'UserProfile');
		$c->leftJoin(modResource::class, 'Resource', 'Thread.resource = Resource.id');
		$c->select($this->modx->getSelectColumns(TicketComment::class, TicketComment::class));
		$c->select([
			'Thread.resource',
			'Thread.properties',
			'thread_name' => 'Thread.name',
			'User.username',
			'UserProfile.fullname',
			'Resource.pagetitle',
			'Resource.context_key',
		]);
		$c->groupby('TicketComment.id');

		return $c;
	}

	/**
	 * @return array
	 */
	public function prepareRow(xPDOObject $object)
	{
		$array = parent::prepareRow($object);

		$array['text'] = strip_tags(html_entity_decode($array['text'], ENT_QUOTES, 'UTF-8'));
		if (!empty($array['fullname'])) {
			$array['name'] = $array['fullname'];
		} elseif (!empty($array['username'])) {
			$array['name'] = $array['username'];
		}
		if (!empty($array['properties']['threadUrl'])) {
			$array['preview_url'] = $array['properties']['threadUrl'];
		} elseif (!empty($array['resource'])) {
			$this->modx->getContext($array['context_key']);
			$array['preview_url'] = $this->modx->makeUrl($array['resource'], $array['context_key']);
		}
		unset($array['properties']);

		$array['actions'] = [];

		// Reply
		if ($this->getProperty('threads') || $this->getProperty('parents')) {
			$array['actions'][] = [
				'cls'    => '',
				'icon'   => 'icon icon-reply',
				'title'  => $this->modx->lexicon('tickets_action_reply'),
				'action' => 'replyComment',
				'button' => true,
				'menu'   => true,
			];
		}

		// Edit
		$array['actions'][] = [
			'cls'    => '',
			'icon'   => 'icon icon-edit',
			'title'  => $this->modx->lexicon('tickets_action_edit'),
			'action' => 'editComment',
			'button' => empty($array['deleted']) || !empty($array['published']),
			'menu'   => true,
		];

		// View
		if (!empty($array['preview_url']) && !empty($array['published']) && empty($array['deleted'])) {
			$array['actions'][] = [
				'cls'    => '',
				'icon'   => 'icon icon-eye',
				'title'  => $this->modx->lexicon('tickets_action_view'),
				'action' => 'viewComment',
				'button' => true,
				'menu'   => true,
			];
		}

		// Publish
		if (!$array['published']) {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-power-off action-green',
				'title'    => $this->modx->lexicon('tickets_action_publish'),
				'multiple' => $this->modx->lexicon('tickets_action_publish'),
				'action'   => 'publishComment',
				'button'   => true,
				'menu'     => true,
			];
		} else {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-power-off action-gray',
				'title'    => $this->modx->lexicon('tickets_action_unpublish'),
				'multiple' => $this->modx->lexicon('tickets_action_unpublish'),
				'action'   => 'unpublishComment',
				'button'   => false,
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
				'action'   => 'deleteComment',
				'button'   => false,
				'menu'     => true,
			];
		} else {
			$array['actions'][] = [
				'cls'      => '',
				'icon'     => 'icon icon-undo action-green',
				'title'    => $this->modx->lexicon('tickets_action_undelete'),
				'multiple' => $this->modx->lexicon('tickets_action_undelete'),
				'action'   => 'undeleteComment',
				'button'   => true,
				'menu'     => true,
			];
		}

		$array['actions'][] = [
			'cls'      => '',
			'icon'     => 'icon icon-trash-o action-red',
			'title'    => $this->modx->lexicon('tickets_action_remove'),
			'multiple' => $this->modx->lexicon('tickets_action_remove'),
			'action'   => 'removeComment',
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
