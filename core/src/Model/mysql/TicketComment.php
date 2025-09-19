<?php
namespace Tickets\Model\mysql;

use xPDO\xPDO;

class TicketComment extends \Tickets\Model\TicketComment
{

    public static $metaMap = array (
        'package' => 'Tickets\\Model\\',
        'version' => '3.0',
        'table' => 'tickets_comments',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'thread' => 0,
            'parent' => 0,
            'text' => '',
            'raw' => '',
            'name' => '',
            'email' => '',
            'ip' => '0.0.0.0',
            'rating' => 0,
            'rating_plus' => 0,
            'rating_minus' => 0,
            'createdon' => NULL,
            'createdby' => 0,
            'editedon' => NULL,
            'editedby' => 0,
            'published' => 1,
            'deleted' => 0,
            'deletedon' => NULL,
            'deletedby' => 0,
            'properties' => NULL,
        ),
        'fieldMeta' => 
        array (
            'thread' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
            ),
            'parent' => 
            array (
                'dbtype' => 'integer',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
            ),
            'text' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'raw' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'name' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'email' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'ip' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '16',
                'phptype' => 'string',
                'null' => false,
                'default' => '0.0.0.0',
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
                'default' => 0,
            ),
            'rating_minus' => 
            array (
                'dbtype' => 'smallint',
                'precision' => '5',
                'phptype' => 'integer',
                'null' => true,
                'default' => 0,
            ),
            'createdon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'createdby' => 
            array (
                'dbtype' => 'integer',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
            ),
            'editedon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'editedby' => 
            array (
                'dbtype' => 'integer',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
            ),
            'published' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'phptype' => 'boolean',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 1,
            ),
            'deleted' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'phptype' => 'boolean',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
            ),
            'deletedon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'deletedby' => 
            array (
                'dbtype' => 'integer',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
            ),
            'properties' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'json',
                'null' => true,
            ),
        ),
        'indexes' => 
        array (
            'thread' => 
            array (
                'alias' => 'thread',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'thread' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'parent' => 
            array (
                'alias' => 'parent',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'parent' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'deleted' => 
            array (
                'alias' => 'deleted',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'deleted' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'published' => 
            array (
                'alias' => 'published',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'published' => 
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
        'composites' => 
        array (
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
                        'class' => '\\Tickets\\Model\\TicketComment',
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
                        'class' => '\\Tickets\\Model\\TicketComment',
                    ),
                ),
            ),
        ),
        'aggregates' => 
        array (
            'User' => 
            array (
                'class' => '\\MODX\\Revolution\\modUser',
                'local' => 'createdby',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'UserProfile' => 
            array (
                'class' => '\\MODX\\Revolution\\modUserProfile',
                'local' => 'createdby',
                'foreign' => 'internalKey',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Thread' => 
            array (
                'class' => '\\Tickets\\Model\\TicketThread',
                'local' => 'thread',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Parent' => 
            array (
                'class' => '\\Tickets\\Model\\TicketComment',
                'local' => 'parent',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Children' => 
            array (
                'class' => '\\Tickets\\Model\\TicketComment',
                'local' => 'id',
                'foreign' => 'parent',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );

}
