<?php

namespace Tickets\Model\mysql;

class TicketQueue extends \Tickets\Model\TicketQueue
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'table' => 'tickets_mail_queues',
		'extends' => 'xPDO\\Om\\xPDOSimpleObject',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
			'timestamp' => 'CURRENT_TIMESTAMP',
			'uid' => 0,
			'subject' => '',
			'body' => '',
			'email' => '',
		],
		'fieldMeta' => [
			'timestamp' => [
				'dbtype' => 'timestamp',
				'phptype' => 'timestamp',
				'null' => false,
				'default' => 'CURRENT_TIMESTAMP',
			],
			'uid' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'subject' => [
				'dbtype' => 'varchar',
				'precision' => '255',
				'phptype' => 'string',
				'null' => true,
				'default' => '',
			],
			'body' => [
				'dbtype' => 'text',
				'phptype' => 'string',
				'null' => true,
				'default' => '',
			],
			'email' => [
				'dbtype' => 'varchar',
				'precision' => '255',
				'phptype' => 'string',
				'null' => true,
				'default' => '',
			],
		],
		'indexes' => [
			'uid' => [
				'alias' => 'uid',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'uid' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'email' => [
				'alias' => 'email',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'email' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
		],
		'aggregates' => [
			'User' => [
				'class' => '\\MODX\\Revolution\\modUser',
				'local' => 'uid',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
		],
	];
}
