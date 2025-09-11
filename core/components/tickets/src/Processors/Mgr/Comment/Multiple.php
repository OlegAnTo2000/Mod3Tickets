<?php

namespace Tickets\Processors\Mgr\Comment;

use \MODX\Revolution\modX;
use \MODX\Revolution\Processors\Processor;
use \xPDO\Om\xPDOQuery;
use \xPDO\Om\xPDOObject;
use Tickets\Tickets;

class Multiple extends Processor
{

    /**
     * @return array|string
     */
    public function process()
    {
        if (!$method = $this->getProperty('method', false)) {
            return $this->failure();
        }
        $ids = json_decode($this->getProperty('ids'), true);
        if (empty($ids)) {
            return $this->success();
        }

        /** @var Tickets $Tickets */
        $Tickets = $this->modx->services->get('Tickets');

        foreach ($ids as $id) {
            $response = $Tickets->runProcessor('mgr/comment/' . $method, array('id' => $id));
            if ($response->isError()) {
                return $response->getResponse();
            }
        }

        return $this->success();
    }

}

return 'TicketCommentMultipleProcessor';