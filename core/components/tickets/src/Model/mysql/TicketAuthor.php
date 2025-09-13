<?php

namespace Tickets\Model\mysql;

class TicketAuthor extends \Tickets\Model\TicketAuthor
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'table' => 'tickets_authors',
		'extends' => 'xPDO\\Om\\xPDOObject',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
			'id' => null,
			'rating' => 0.0,
			'createdon' => null,
			'visitedon' => null,
			'tickets' => 0,
			'comments' => 0,
			'views' => 0,
			'votes_tickets' => 0.0,
			'votes_comments' => 0.0,
			'stars_tickets' => 0,
			'stars_comments' => 0,
			'votes_tickets_up' => 0,
			'votes_tickets_down' => 0,
			'votes_comments_up' => 0,
			'votes_comments_down' => 0,
			'properties' => null,
		],
		'fieldMeta' => [
			'id' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'index' => 'pk',
			],
			'rating' => [
				'dbtype' => 'decimal',
				'precision' => '12,2',
				'phptype' => 'float',
				'null' => true,
				'default' => 0.0,
			],
			'createdon' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => true,
			],
			'visitedon' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => true,
			],
			'tickets' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => true,
				'default' => 0,
			],
			'comments' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => true,
				'default' => 0,
			],
			'views' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => true,
				'default' => 0,
			],
			'votes_tickets' => [
				'dbtype' => 'decimal',
				'precision' => '12,2',
				'phptype' => 'float',
				'null' => true,
				'default' => 0.0,
			],
			'votes_comments' => [
				'dbtype' => 'decimal',
				'precision' => '12,2',
				'phptype' => 'float',
				'null' => true,
				'default' => 0.0,
			],
			'stars_tickets' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => true,
				'default' => 0,
			],
			'stars_comments' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => true,
				'default' => 0,
			],
			'votes_tickets_up' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => true,
				'default' => 0,
			],
			'votes_tickets_down' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => true,
				'default' => 0,
			],
			'votes_comments_up' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => true,
				'default' => 0,
			],
			'votes_comments_down' => [
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
			'PRIMARY' => [
				'alias' => 'PRIMARY',
				'primary' => true,
				'unique' => true,
				'type' => 'BTREE',
				'columns' => [
					'id' => [
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
			'createdon' => [
				'alias' => 'createdon',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'createdon' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'visitedon' => [
				'alias' => 'visitedon',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'visitedon' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'tickets' => [
				'alias' => 'tickets',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'tickets' => [
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
			'views' => [
				'alias' => 'views',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'views' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'votes' => [
				'alias' => 'stars_comments',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'stars_comments' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
		],
		'composites' => [
			'Actions' => [
				'class' => 'TicketAuthorAction',
				'local' => 'id',
				'foreign' => 'createdby',
				'cardinality' => 'many',
				'owner' => 'local',
			],
		],
		'aggregates' => [
			'User' => [
				'class' => 'MODX\\Revolution\\modUser',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'UserProfile' => [
				'class' => 'MODX\\Revolution\\modUserProfile',
				'local' => 'id',
				'foreign' => 'internalKey',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
		],
	];
}
