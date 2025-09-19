<?php
namespace Tickets\Model\mysql;

use xPDO\xPDO;

class TicketTotal extends \Tickets\Model\TicketTotal
{

    public static $metaMap = array (
        'package' => 'Tickets\\Model\\',
        'version' => '3.0',
        'table' => 'tickets_totals',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'id' => NULL,
            'class' => NULL,
            'tickets' => 0,
            'comments' => 0,
            'views' => 0,
            'stars' => 0,
            'rating' => 0,
            'rating_plus' => 0,
            'rating_minus' => 0,
        ),
        'fieldMeta' => 
        array (
            'id' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'index' => 'pk',
            ),
            'class' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '100',
                'phptype' => 'string',
                'null' => false,
                'index' => 'pk',
            ),
            'tickets' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => true,
                'default' => 0,
            ),
            'comments' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => true,
                'default' => 0,
            ),
            'views' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'null' => true,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'stars' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'null' => true,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'rating' => 
            array (
                'dbtype' => 'smallint',
                'precision' => '5',
                'phptype' => 'integer',
                'null' => true,
                'default' => 0,
            ),
            'rating_plus' => 
            array (
                'dbtype' => 'smallint',
                'precision' => '5',
                'phptype' => 'integer',
                'null' => true,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'rating_minus' => 
            array (
                'dbtype' => 'smallint',
                'precision' => '5',
                'phptype' => 'integer',
                'null' => true,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
        ),
        'indexes' => 
        array (
            'PRIMARY' => 
            array (
                'alias' => 'PRIMARY',
                'primary' => true,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'id' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                    'class' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'views' => 
            array (
                'alias' => 'views',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'views' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'comments' => 
            array (
                'alias' => 'comments',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'comments' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'stars' => 
            array (
                'alias' => 'stars',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'stars' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'tickets' => 
            array (
                'alias' => 'tickets',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'tickets' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'rating' => 
            array (
                'alias' => 'rating',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'rating' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
        'aggregates' => 
        array (
            'User' => 
            array (
                'class' => '\\MODX\\Revolution\\modUser',
                'local' => 'uid',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'TicketsSection' => 
            array (
                'class' => '\\Tickets\\Model\\Ticket',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Ticket' => 
            array (
                'class' => '\\Tickets\\Model\\Ticket',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'TicketComment' => 
            array (
                'class' => '\\Tickets\\Model\\Ticket',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'TicketThread' => 
            array (
                'class' => '\\Tickets\\Model\\Ticket',
                'local' => 'id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );

}
