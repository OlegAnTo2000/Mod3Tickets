<?php

namespace Tickets\Processors\Mgr\Author;

use \MODX\Revolution\modX;
use \MODX\Revolution\Processors\Processor;
use \xPDO\Om\xPDOQuery;
use \xPDO\Om\xPDOObject;

class Rebuild extends Processor
{

    /**
     * @return array|string
     */
    public function process()
    {
        $time = time();
        $time_limit = @ini_get('max_execution_time') - 20;
        if ($time_limit <= 5) {
            $time_limit = 5;
        }

        $start = $this->getProperty('start', 0);
        $c = $this->modx->newQuery('MODX\Revolution\modUser');
        if ($start == 0) {
            $this->cleanTables();
        } else {
            $c->limit(1000000, $start);
        }
        $users = $this->modx->getIterator('MODX\Revolution\modUser', $c);
        /** @var modUser $user */
        foreach ($users as $user) {
            /** @var TicketAuthor $profile */
            if (!$profile = $user->getOne('Tickets\Model\TicketAuthor')) {
                $profile = $this->modx->newObject('Tickets\Model\TicketAuthor');
                $user->addOne($profile);
            }
            $profile->refreshActions(false);
            $start++;
            if ((time() - $time) >= $time_limit) {
                return $this->cleanup($start);
            }
        }

        return $this->cleanup($start);
    }


    /**
     * @param int $processed
     *
     * @return array|string
     */
    public function cleanup($processed = 0)
    {
        return $this->success('', array(
            'total' => $this->modx->getCount('MODX\Revolution\modUser'),
            'processed' => $processed,
        ));
    }


    /**
     *
     */
    protected function cleanTables()
    {
        $c = $this->modx->newQuery('Tickets\Model\TicketAuthor');
        $c->command('UPDATE');
        $c->set(array(
            'tickets' => 0,
            'comments' => 0,
            'views' => 0,
            'stars_tickets' => 0,
            'stars_comments' => 0,
            'votes_tickets' => 0,
            'votes_comments' => 0,
            'votes_tickets_up' => 0,
            'votes_tickets_down' => 0,
            'votes_comments_up' => 0,
            'votes_comments_down' => 0,
        ));
        $c->prepare();
        $c->stmt->execute();

        $this->modx->removeCollection('Tickets\Model\TicketAuthorAction', array());
    }


}

return 'TicketAuthorRebuildProcessor';