<?php

namespace Tickets\Processors\Mgr\Comment;

use function html_entity_decode;

use MODX\Revolution\Processors\Model\GetProcessor;

use function strftime;
use function strtotime;

use Tickets\Model\TicketComment;

class Get extends GetProcessor
{
	public $objectType     = TicketComment::class;
	public $classKey       = TicketComment::class;
	public $languageTopics = ['tickets:default'];

	/**
	 * @return array|string
	 */
	public function cleanup()
	{
		$comment              = $this->object->toArray();
		$comment['createdon'] = $this->formatDate($comment['createdon']);
		$comment['editedon']  = $this->formatDate($comment['editedon']);
		$comment['deletedon'] = $this->formatDate($comment['deletedon']);
		$comment['text']      = !empty($comment['raw'])
			? html_entity_decode($comment['raw'])
			: html_entity_decode($comment['text']);

		return $this->success('', $comment);
	}

	/**
	 * @param string $date
	 *
	 * @return string|null
	 */
	public function formatDate($date = '')
	{
		if (empty($date) || '0000-00-00 00:00:00' == $date) {
			return $this->modx->lexicon('no');
		}

		return strftime($this->modx->getOption('tickets.date_format'), strtotime($date));
	}
}
