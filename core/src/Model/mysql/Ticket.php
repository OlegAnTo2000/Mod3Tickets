<?php
namespace Tickets\Model\mysql;

use xPDO\xPDO;

class Ticket extends \Tickets\Model\Ticket
{

    public static $metaMap = array (
        'package' => 'Tickets\\Model\\',
        'version' => '3.0',
        'extends' => '\\MODX\\Revolution\\modResource',
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
                'class' => '\\Tickets\\Model\\TicketView',
                'local' => 'id',
                'foreign' => 'parent',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'Votes' => 
            array (
                'class' => '\\Tickets\\Model\\TicketVote',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'many',
                'owner' => 'local',
                'criteria' => 
                array (
                    'foreign' => 
                    array (
                        'class' => '\\Tickets\\Model\\Ticket',
                    ),
                ),
            ),
            'Stars' => 
            array (
                'class' => '\\Tickets\\Model\\TicketStar',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'many',
                'owner' => 'local',
                'criteria' => 
                array (
                    'foreign' => 
                    array (
                        'class' => '\\Tickets\\Model\\Ticket',
                    ),
                ),
            ),
            'Files' => 
            array (
                'class' => '\\Tickets\\Model\\TicketFile',
                'local' => 'id',
                'foreign' => 'parent',
                'cardinality' => 'many',
                'owner' => 'local',
                'criteria' => 
                array (
                    'foreign' => 
                    array (
                        'class' => '\\Tickets\\Model\\Ticket',
                    ),
                ),
            ),
            'Total' => 
            array (
                'class' => '\\Tickets\\Model\\TicketTotal',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'local',
                'criteria' => 
                array (
                    'foreign' => 
                    array (
                        'class' => '\\Tickets\\Model\\Ticket',
                    ),
                ),
            ),
        ),
        'aggregates' => 
        array (
            'Section' => 
            array (
                'class' => '\\Tickets\\Model\\TicketsSection',
                'local' => 'parent',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Threads' => 
            array (
                'class' => '\\Tickets\\Model\\TicketThread',
                'local' => 'id',
                'foreign' => 'resource',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );

}
