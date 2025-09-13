<?php

namespace Tickets\Model\mysql;

class TicketAuthorAction extends \Tickets\Model\TicketAuthorAction
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'table' => 'tickets_author_actions',
		'extends' => 'xPDO\\Om\\xPDOObject',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
			'id' => null,
			'action' => null,
			'owner' => 0,
			'rating' => 0.0,
			'multiplier' => 1,
			'ticket' => 0,
			'section' => 0,
			'createdby' => 0,
			'createdon' => null,
			'year' => '0000',
			'month' => 0,
			'day' => 0,
		],
		'fieldMeta' => [
			'id' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'null' => false,
				'index' => 'pk',
			],
			'action' => [
				'dbtype' => 'varchar',
				'precision' => '50',
				'phptype' => 'string',
				'null' => false,
				'index' => 'pk',
			],
			'owner' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'null' => false,
				'default' => 0,
				'index' => 'pk',
			],
			'rating' => [
				'dbtype' => 'decimal',
				'precision' => '12,2',
				'phptype' => 'float',
				'null' => true,
				'default' => 0.0,
			],
			'multiplier' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'null' => true,
				'default' => 1,
			],
			'ticket' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'null' => false,
				'default' => 0,
			],
			'section' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'null' => false,
				'default' => 0,
			],
			'createdby' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'null' => false,
				'default' => 0,
				'index' => 'pk',
			],
			'createdon' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => false,
			],
			'year' => [
				'dbtype' => 'year',
				'precision' => '4',
				'phptype' => 'integer',
				'null' => true,
				'default' => '0000',
			],
			'month' => [
				'dbtype' => 'int',
				'precision' => '2',
				'phptype' => 'integer',
				'null' => true,
				'default' => 0,
			],
			'day' => [
				'dbtype' => 'int',
				'precision' => '2',
				'phptype' => 'integer',
				'null' => true,
				'default' => 0,
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
					'action' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
					'owner' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
					'createdby' => [
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
			'section' => [
				'alias' => 'section',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'section' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'ticket' => [
				'alias' => 'ticket',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'ticket' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'action' => [
				'alias' => 'action',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'action' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'owner' => [
				'alias' => 'owner',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'owner' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'date' => [
				'alias' => 'date',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'year' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
					'month' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
					'day' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
		],
		'aggregates' => [
			'User' => [
				'class' => 'MODX\\Revolution\\modUser',
				'local' => 'createdby',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'UserProfile' => [
				'class' => 'MODX\\Revolution\\modUserProfile',
				'local' => 'createdby',
				'foreign' => 'internalKey',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'Author' => [
				'class' => 'TicketAuthor',
				'local' => 'createdby',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'Ticket' => [
				'class' => 'Ticket',
				'local' => 'ticket',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'Section' => [
				'class' => 'TicketsSection',
				'local' => 'section',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
		],
	];
}
