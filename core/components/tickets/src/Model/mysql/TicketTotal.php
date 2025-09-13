<?php

namespace Tickets\Model\mysql;

class TicketTotal extends \Tickets\Model\TicketTotal
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'table' => 'tickets_totals',
		'extends' => 'xPDO\\Om\\xPDOObject',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
			'id' => null,
			'class' => null,
			'tickets' => 0,
			'comments' => 0,
			'views' => 0,
			'stars' => 0,
			'rating' => 0,
			'rating_plus' => 0,
			'rating_minus' => 0,
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
			'class' => [
				'dbtype' => 'varchar',
				'precision' => '100',
				'phptype' => 'string',
				'null' => false,
				'index' => 'pk',
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
				'null' => true,
				'attributes' => 'unsigned',
				'default' => 0,
			],
			'stars' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'null' => true,
				'attributes' => 'unsigned',
				'default' => 0,
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
				'attributes' => 'unsigned',
				'default' => 0,
			],
			'rating_minus' => [
				'dbtype' => 'smallint',
				'precision' => '5',
				'phptype' => 'integer',
				'null' => true,
				'attributes' => 'unsigned',
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
					'class' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'owner' => [
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
		'aggregates' => [
			'User' => [
				'class' => 'MODX\\Revolution\\modUser',
				'local' => 'uid',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'TicketsSection' => [
				'class' => 'Ticket',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
				'criteria' => [
					'local' => [
						'class' => 'TicketsSection',
					],
				],
			],
			'Ticket' => [
				'class' => 'Ticket',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
				'criteria' => [
					'local' => [
						'class' => 'Ticket',
					],
				],
			],
			'TicketComment' => [
				'class' => 'Ticket',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
				'criteria' => [
					'local' => [
						'class' => 'TicketComment',
					],
				],
			],
			'TicketThread' => [
				'class' => 'Ticket',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
				'criteria' => [
					'local' => [
						'class' => 'TicketThread',
					],
				],
			],
		],
	];
}
