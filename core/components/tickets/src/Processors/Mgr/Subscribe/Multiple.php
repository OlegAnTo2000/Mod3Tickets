<?php

namespace Tickets\Processors\Mgr\Subscribe;

use \MODX\Revolution\modX;
use \xPDO\Om\xPDOQuery;
use \xPDO\Om\xPDOObject;

class Multiple extends \MODX\Revolution\Processors\Processor
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
        $parents = intval($this->getProperty('parents'));

        if (empty($ids) || empty($parents)) {
            return $this->success();
        }

        /** @var Tickets $Tickets */
        $Tickets = $this->modx->getService('Tickets');

        /** @var modProcessorResponse $response */
        $response = $Tickets->runProcessor('mgr/subscribe/' . $method, array('ids' => $ids, 'parents' => $parents));
        if ($response->isError()) {
            return $response->getResponse();
        }

        return $this->success();
    }

}

return 'TicketSubscribeMultipleProcessor';