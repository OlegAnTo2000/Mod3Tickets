<?php

namespace Tickets\Processors\Mgr\Comment;

use MODX\Revolution\modResource;
use MODX\Revolution\modUser;
use MODX\Revolution\modUserProfile;
use MODX\Revolution\Processors\Model\GetListProcessor;
use Tickets\Model\TicketComment;
use Tickets\Model\TicketThread;
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
	 * Фильтрация перед подсчётом total
	 */
	public function prepareQueryBeforeCount(xPDOQuery $c): xPDOQuery
	{
		// По разделу
		if ($section = (int)$this->getProperty('section')) {
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
		}
		// По списку тредов
		elseif ($threads = $this->getProperty('threads')) {
			if (!is_array($threads)) {
				$threads = explode(',', $threads);
			}
			if (!empty($threads)) {
				$c->where(['TicketComment.thread:IN' => $threads]);
			}
		}
		// По списку ресурсов
		elseif ($parents = $this->getProperty('parents')) {
			if (!is_array($parents)) {
				$parents = explode(',', $parents);
			}
			if (!empty($parents)) {
				$c->where(['Thread.resource:IN' => $parents]);
			}
		}

		// Поисковый запрос
		if ($query = $this->getProperty('query')) {
			$query = trim($query);
			if (is_numeric($query)) {
				$c->where([
					'TicketComment.id:='        => $query,
					'OR:TicketComment.parent:=' => $query,
				]);
			} else {
				$c->where([
					'TicketComment.text:LIKE'     => "%{$query}%",
					'OR:TicketComment.raw:LIKE'   => "%{$query}%",
					'OR:TicketComment.name:LIKE'  => "%{$query}%",
					'OR:TicketComment.email:LIKE' => "%{$query}%",
				]);
			}
		}

		return $c;
	}

	/**
	 * Присоединяем таблицы и выбираем нужные поля
	 */
	public function prepareQueryAfterCount(xPDOQuery $c): xPDOQuery
	{
		$c->leftJoin(TicketThread::class, 'Thread');
		$c->leftJoin(modUser::class, 'User');
		$c->leftJoin(modUserProfile::class, 'UserProfile');
		$c->leftJoin(modResource::class, 'Resource', 'Thread.resource = Resource.id');

		$c->select($this->modx->getSelectColumns(TicketComment::class, 'TicketComment'));
		$c->select([
			'Thread.resource',
			'Thread.properties',
			'thread_name' => 'Thread.name',
			'User.username',
			'UserProfile.fullname',
			'Resource.pagetitle',
			'Resource.context_key',
		]);

		return $c;
	}

	/**
	 * Преобразуем строку для ответа
	 */
	public function prepareRow(xPDOObject $object): array
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

		// --- Actions ---
		$array['actions'] = [];

		if ($this->getProperty('threads') || $this->getProperty('parents')) {
			$array['actions'][] = [
				'icon'   => 'icon icon-reply',
				'title'  => $this->modx->lexicon('tickets_action_reply'),
				'action' => 'replyComment',
				'button' => true,
				'menu'   => true,
			];
		}

		$array['actions'][] = [
			'icon'   => 'icon icon-edit',
			'title'  => $this->modx->lexicon('tickets_action_edit'),
			'action' => 'editComment',
			'button' => empty($array['deleted']) || !empty($array['published']),
			'menu'   => true,
		];

		if (!empty($array['preview_url']) && !empty($array['published']) && empty($array['deleted'])) {
			$array['actions'][] = [
				'icon'   => 'icon icon-eye',
				'title'  => $this->modx->lexicon('tickets_action_view'),
				'action' => 'viewComment',
				'button' => true,
				'menu'   => true,
			];
		}

		if (!$array['published']) {
			$array['actions'][] = [
				'icon'     => 'icon icon-power-off action-green',
				'title'    => $this->modx->lexicon('tickets_action_publish'),
				'multiple' => $this->modx->lexicon('tickets_action_publish'),
				'action'   => 'publishComment',
				'button'   => true,
				'menu'     => true,
			];
		} else {
			$array['actions'][] = [
				'icon'     => 'icon icon-power-off action-gray',
				'title'    => $this->modx->lexicon('tickets_action_unpublish'),
				'multiple' => $this->modx->lexicon('tickets_action_unpublish'),
				'action'   => 'unpublishComment',
				'button'   => false,
				'menu'     => true,
			];
		}

		if (!$array['deleted']) {
			$array['actions'][] = [
				'icon'     => 'icon icon-trash-o action-yellow',
				'title'    => $this->modx->lexicon('tickets_action_delete'),
				'multiple' => $this->modx->lexicon('tickets_action_delete'),
				'action'   => 'deleteComment',
				'button'   => false,
				'menu'     => true,
			];
		} else {
			$array['actions'][] = [
				'icon'     => 'icon icon-undo action-green',
				'title'    => $this->modx->lexicon('tickets_action_undelete'),
				'multiple' => $this->modx->lexicon('tickets_action_undelete'),
				'action'   => 'undeleteComment',
				'button'   => true,
				'menu'     => true,
			];
		}

		$array['actions'][] = [
			'icon'     => 'icon icon-trash-o action-red',
			'title'    => $this->modx->lexicon('tickets_action_remove'),
			'multiple' => $this->modx->lexicon('tickets_action_remove'),
			'action'   => 'removeComment',
			'button'   => false,
			'menu'     => true,
		];

		$array['actions'][] = [
			'icon'   => 'icon icon-cog actions-menu',
			'menu'   => false,
			'button' => true,
			'action' => 'showMenu',
			'type'   => 'menu',
		];

		return $array;
	}
}
