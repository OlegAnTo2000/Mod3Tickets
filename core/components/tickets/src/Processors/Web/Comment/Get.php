<?php

namespace Tickets\Processors\Web\Comment;

use Tickets\TicketComment;
use MODX\Revolution\Processors\Model\GetProcessor;

class Get extends GetProcessor
{
    public $objectType     = TicketComment::class;
    public $classKey       = TicketComment::class;
    public $languageTopics = array('tickets:default');

    /**
     * @return array|string
     */
    public function cleanup()
    {
        $comment = $this->object->toArray();
        $comment['text'] = html_entity_decode($comment['text']);
        $comment['raw'] = html_entity_decode($comment['raw']);

        return $this->success('', $comment);
    }

}