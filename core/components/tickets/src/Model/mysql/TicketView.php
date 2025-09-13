<?php

namespace Tickets\Model\mysql;

class TicketView extends \Tickets\Model\TicketView
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'table' => 'tickets_views',
		'extends' => 'xPDO\\Om\\xPDOObject',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
			'parent' => 0,
			'uid' => 0,
			'guest_key' => '',
			'timestamp' => null,
		],
		'fieldMeta' => [
			'parent' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
				'index' => 'pk',
			],
			'uid' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
				'index' => 'pk',
			],
			'guest_key' => [
				'dbtype' => 'char',
				'precision' => '32',
				'phptype' => 'string',
				'null' => false,
				'default' => '',
				'index' => 'pk',
			],
			'timestamp' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => false,
			],
		],
		'indexes' => [
			'PRIMARY' => [
				'alias' => 'PRIMARY',
				'primary' => true,
				'unique' => true,
				'type' => 'BTREE',
				'columns' => [
					'parent' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
					'uid' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
					'guest_key' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
		],
		'aggregates' => [
			'Ticket' => [
				'class' => 'Ticket',
				'local' => 'parent',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
		],
	];
}
