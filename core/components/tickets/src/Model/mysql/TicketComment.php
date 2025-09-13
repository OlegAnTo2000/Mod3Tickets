<?php

namespace Tickets\Model\mysql;

class TicketComment extends \Tickets\Model\TicketComment
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'table' => 'tickets_comments',
		'extends' => 'xPDO\\Om\\xPDOSimpleObject',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
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
			'createdon' => null,
			'createdby' => 0,
			'editedon' => null,
			'editedby' => 0,
			'published' => 1,
			'deleted' => 0,
			'deletedon' => null,
			'deletedby' => 0,
			'properties' => null,
		],
		'fieldMeta' => [
			'thread' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'parent' => [
				'dbtype' => 'integer',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'text' => [
				'dbtype' => 'text',
				'phptype' => 'string',
				'null' => false,
				'default' => '',
			],
			'raw' => [
				'dbtype' => 'text',
				'phptype' => 'string',
				'null' => false,
				'default' => '',
			],
			'name' => [
				'dbtype' => 'varchar',
				'precision' => '255',
				'phptype' => 'string',
				'null' => false,
				'default' => '',
			],
			'email' => [
				'dbtype' => 'varchar',
				'precision' => '255',
				'phptype' => 'string',
				'null' => false,
				'default' => '',
			],
			'ip' => [
				'dbtype' => 'varchar',
				'precision' => '16',
				'phptype' => 'string',
				'null' => false,
				'default' => '0.0.0.0',
			],
			'rating' => [
				'dbtype' => 'smallint',
				'precision' => '5',
				'phptype' => 'integer',
				'null' => true,
				'default' => 0,
			],
			'rating_plus' => [
				'dbtype' => 'smallint',
				'precision' => '5',
				'phptype' => 'integer',
				'null' => true,
				'default' => 0,
			],
			'rating_minus' => [
				'dbtype' => 'smallint',
				'precision' => '5',
				'phptype' => 'integer',
				'null' => true,
				'default' => 0,
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
			'editedon' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => true,
			],
			'editedby' => [
				'dbtype' => 'integer',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'published' => [
				'dbtype' => 'tinyint',
				'precision' => '1',
				'phptype' => 'boolean',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 1,
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
			'properties' => [
				'dbtype' => 'text',
				'phptype' => 'json',
				'null' => true,
			],
		],
		'indexes' => [
			'thread' => [
				'alias' => 'thread',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'thread' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'parent' => [
				'alias' => 'parent',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'parent' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'deleted' => [
				'alias' => 'deleted',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'deleted' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'published' => [
				'alias' => 'published',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'published' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'rating' => [
				'alias' => 'rating',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'rating' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
		],
		'composites' => [
			'Votes' => [
				'class' => 'TicketVote',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'many',
				'owner' => 'local',
				'criteria' => [
					'foreign' => [
						'class' => 'TicketComment',
					],
				],
			],
			'Total' => [
				'class' => 'TicketTotal',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'local',
				'criteria' => [
					'foreign' => [
						'class' => 'TicketComment',
					],
				],
			],
		],
		'aggregates' => [
			'User' => [
				'class' => '\\MODX\\Revolution\\modUser',
				'local' => 'createdby',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'UserProfile' => [
				'class' => '\\MODX\\Revolution\\modUserProfile',
				'local' => 'createdby',
				'foreign' => 'internalKey',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'Thread' => [
				'class' => 'TicketThread',
				'local' => 'thread',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'Parent' => [
				'class' => 'TicketComment',
				'local' => 'parent',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'Children' => [
				'class' => 'TicketComment',
				'local' => 'id',
				'foreign' => 'parent',
				'cardinality' => 'many',
				'owner' => 'local',
			],
		],
	];
}
