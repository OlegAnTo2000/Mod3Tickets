<?php

namespace Tickets\Model\mysql;

class TicketVote extends \Tickets\Model\TicketVote
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'table' => 'tickets_votes',
		'extends' => 'xPDO\\Om\\xPDOObject',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
			'id' => 0,
			'class' => null,
			'owner' => 0,
			'value' => 0,
			'createdon' => null,
			'createdby' => 0,
			'ip' => '0.0.0.0',
		],
		'fieldMeta' => [
			'id' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
				'index' => 'pk',
			],
			'class' => [
				'dbtype' => 'varchar',
				'precision' => '100',
				'phptype' => 'string',
				'null' => false,
				'index' => 'pk',
			],
			'owner' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
				'index' => 'index',
			],
			'value' => [
				'dbtype' => 'tinyint',
				'precision' => '1',
				'phptype' => 'integer',
				'null' => false,
				'default' => 0,
			],
			'createdon' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => true,
				'index' => 'index',
			],
			'createdby' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
				'index' => 'pk',
			],
			'ip' => [
				'dbtype' => 'varchar',
				'precision' => '16',
				'phptype' => 'string',
				'null' => true,
				'default' => '0.0.0.0',
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
					'createdby' => [
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
		],
	];
}
