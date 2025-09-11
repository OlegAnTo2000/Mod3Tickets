<?php

namespace Tickets\Processors\Web\Ticket;

use Tickets\Ticket;
use xPDO\Om\xPDOQuery;
use MODX\Revolution\Processors\Model\GetListProcessor;

class GetList extends GetListProcessor
{
    public $objectType = Ticket::class;
    public $classKey = Ticket::class;
    public $languageTopics = array('tickets:default');
    public $defaultSortField = 'createdon';
    public $defaultSortDirection = 'DESC';

    /**
     * @param xPDOQuery $c
     *
     * @return xPDOQuery
     */
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        if ($parents = $this->getProperty('parents')) {
            if (!is_array($parents)) {
                $parents = explode(',', $parents);
            }
            $c->where(array('parent:IN' => $parents));
        }

        $c->where(array(
            'class_key' => Ticket::class,
            'published' => 1,
            'deleted'   => 0,
        ));

        return $c;
    }

}