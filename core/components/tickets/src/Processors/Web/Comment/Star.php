<?php

namespace Tickets\Processors\Web\Comment;

use Tickets\Model\TicketStar;
use Tickets\Model\TicketComment;
use MODX\Revolution\Processors\ModelProcessor;

class Star extends ModelProcessor
{
	public $classKey = TicketStar::class;
	public $permission = 'comment_star';

	/**
	 * @return bool|null|string
	 */
	public function initialize()
	{
		if (!$this->modx->hasPermission($this->permission)) {
			return $this->modx->lexicon('access_denied');
		}

		return true;
	}


	/**
	 * @return array|string
	 */
	public function process()
	{
		$id = (int)$this->getProperty('id');

		/** @var TicketComment $object */
		if (!$object = $this->modx->getObject(TicketComment::class, $id)) {
			return $this->failure($this->modx->lexicon('ticket_comment_err_id', compact('id')));
		}

		$data = array(
			'id'        => $id,
			'class'     => TicketComment::class,
			'createdby' => $this->modx->user->id,
		);

		/** @var TicketStar $star */
		if ($star = $this->modx->getObject($this->classKey, $data)) {
			$event = $this->modx->invokeEvent('OnBeforeCommentUnStar', array(
				$this->objectType => &$star,
				'object'          => &$star,
			));
			if (is_array($event) && !empty($event)) {
				return $this->failure(implode("\n", $event));
			}

			$star->remove();

			$this->modx->invokeEvent('OnCommentUnStar', array(
				$this->objectType => &$star,
				'object'          => &$star,
			));
		} else {
			$star = $this->modx->newObject($this->classKey);
			$data['owner'] = $object->get('createdby');
			$data['createdon'] = date('Y-m-d H:i:s');

			$event = $this->modx->invokeEvent('OnBeforeCommentStar', array(
				$this->objectType => &$star,
				'object' => &$star,
			));
			if (is_array($event) && !empty($event)) {
				return $this->failure(implode("\n", $event));
			}

			$star->fromArray($data, '', true, true);
			$star->save();

			$this->modx->invokeEvent('OnCommentStar', array(
				$this->objectType => &$star,
				'object' => &$star,
			));
		}
		$stars = $this->modx->getCount(TicketStar::class, array('id' => $id, 'class' => TicketComment::class));

		return $this->success('', array('stars' => $stars));
	}
}
