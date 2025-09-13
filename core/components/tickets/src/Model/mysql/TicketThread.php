<?php

namespace Tickets\Model\mysql;

class TicketThread extends \Tickets\Model\TicketThread
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'table' => 'tickets_threads',
		'extends' => 'xPDO\\Om\\xPDOSimpleObject',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
			'resource' => 0,
			'name' => '',
			'subscribers' => '',
			'createdon' => null,
			'createdby' => 0,
			'closed' => 0,
			'deleted' => 0,
			'deletedon' => null,
			'deletedby' => 0,
			'comment_last' => 0,
			'comment_time' => null,
			'comments' => 0,
			'properties' => null,
		],
		'fieldMeta' => [
			'resource' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'name' => [
				'dbtype' => 'varchar',
				'precision' => '191',
				'phptype' => 'string',
				'null' => false,
				'default' => '',
			],
			'subscribers' => [
				'dbtype' => 'text',
				'phptype' => 'json',
				'null' => false,
				'default' => '',
			],
			'createdon' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => true,
			],
			'createdby' => [
				'dbtype' => 'integer',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'closed' => [
				'dbtype' => 'tinyint',
				'precision' => '1',
				'phptype' => 'boolean',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'deleted' => [
				'dbtype' => 'tinyint',
				'precision' => '1',
				'phptype' => 'boolean',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'deletedon' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => true,
			],
			'deletedby' => [
				'dbtype' => 'integer',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'comment_last' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'comment_time' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => true,
				'index' => 'index',
			],
			'comments' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => true,
				'default' => 0,
			],
			'properties' => [
				'dbtype' => 'text',
				'phptype' => 'json',
				'null' => true,
			],
		],
		'indexes' => [
			'name' => [
				'alias' => 'name',
				'primary' => false,
				'unique' => true,
				'type' => 'BTREE',
				'columns' => [
					'name' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'resource' => [
				'alias' => 'resource',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'resource' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'comment_last' => [
				'alias' => 'comment_last',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'comment_last' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'comments' => [
				'alias' => 'comments',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'comments' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'closed' => [
				'alias' => 'closed',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'closed' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
		],
		'composites' => [
			'Comments' => [
				'class' => 'TicketComment',
				'local' => 'id',
				'foreign' => 'thread',
				'cardinality' => 'many',
				'owner' => 'local',
			],
			'Total' => [
				'class' => 'TicketTotal',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'local',
				'criteria' => [
					'foreign' => [
						'class' => 'TicketThread',
					],
				],
			],
		],
		'aggregates' => [
			'Ticket' => [
				'class' => 'Ticket',
				'local' => 'resource',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'Resource' => [
				'class' => 'MODX\\Revolution\\modResource',
				'local' => 'resource',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
		],
	];
}
