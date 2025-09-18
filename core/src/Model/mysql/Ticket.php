<?php
namespace Tickets\Model\mysql;

use xPDO\xPDO;

class Ticket extends \Tickets\Model\Ticket
{

    public static $metaMap = array (
        'package' => 'Tickets\\Model\\',
        'version' => '3.0',
        'extends' => 'MODX\\Revolution\\modResource',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
        ),
        'fieldMeta' => 
        array (
        ),
        'composites' => 
        array (
            'Views' => 
            array (
                'class' => 'TicketView',
                'local' => 'id',
                'foreign' => 'parent',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'Votes' => 
            array (
                'class' => 'TicketVote',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'many',
                'owner' => 'local',
                'criteria' => 
                array (
                    'foreign' => 
                    array (
                        'class' => 'Ticket',
                    ),
                ),
            ),
            'Stars' => 
            array (
                'class' => 'TicketStar',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'many',
                'owner' => 'local',
                'criteria' => 
                array (
                    'foreign' => 
                    array (
                        'class' => 'Ticket',
                    ),
                ),
            ),
            'Files' => 
            array (
                'class' => 'TicketFile',
                'local' => 'id',
                'foreign' => 'parent',
                'cardinality' => 'many',
                'owner' => 'local',
                'criteria' => 
                array (
                    'foreign' => 
                    array (
                        'class' => 'Ticket',
                    ),
                ),
            ),
            'Total' => 
            array (
                'class' => 'TicketTotal',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'local',
                'criteria' => 
                array (
                    'foreign' => 
                    array (
                        'class' => 'Ticket',
                    ),
                ),
            ),
        ),
        'aggregates' => 
        array (
            'Section' => 
            array (
                'class' => 'TicketsSection',
                'local' => 'parent',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Threads' => 
            array (
                'class' => 'TicketThread',
                'local' => 'id',
                'foreign' => 'resource',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );

}
