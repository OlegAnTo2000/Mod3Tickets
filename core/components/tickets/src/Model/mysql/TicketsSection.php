<?php
namespace Tickets\Model\mysql;

use xPDO\xPDO;

class TicketsSection extends \Tickets\Model\TicketsSection
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
            'Tickets' => 
            array (
                'class' => 'Ticket',
                'local' => 'id',
                'foreign' => 'parent',
                'cardinality' => 'many',
                'owner' => 'local',
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
                        'class' => 'TicketsSection',
                    ),
                ),
            ),
        ),
    );

}
