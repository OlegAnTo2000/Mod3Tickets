<?php

namespace Tickets\Model\mysql;

class TicketFile extends \Tickets\Model\TicketFile
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'table' => 'tickets_files',
		'extends' => 'xPDO\\Om\\xPDOSimpleObject',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
			'parent' => 0,
			'class' => null,
			'source' => 1,
			'name' => null,
			'description' => null,
			'path' => null,
			'file' => null,
			'type' => null,
			'size' => 0,
			'createdon' => null,
			'createdby' => 0,
			'rank' => 0,
			'url' => null,
			'thumb' => null,
			'thumbs' => null,
			'deleted' => 0,
			'properties' => null,
			'hash' => '',
		],
		'fieldMeta' => [
			'parent' => [
				'dbtype' => 'int',
				'precision' => '10',
				'phptype' => 'integer',
				'attributes' => 'unsigned',
				'null' => false,
				'default' => 0,
			],
			'class' => [
				'dbtype' => 'varchar',
				'precision' => '100',
				'phptype' => 'string',
			],
			'source' => [
				'dbtype' => 'int',
				'precision' => '10',
				'attributes' => 'unsigned',
				'phptype' => 'integer',
				'null' => true,
				'default' => 1,
			],
			'name' => [
				'dbtype' => 'varchar',
				'precision' => '255',
				'phptype' => 'string',
				'null' => false,
			],
			'description' => [
				'dbtype' => 'text',
				'phptype' => 'string',
				'null' => true,
			],
			'path' => [
				'dbtype' => 'varchar',
				'precision' => '255',
				'phptype' => 'string',
				'null' => false,
			],
			'file' => [
				'dbtype' => 'varchar',
				'precision' => '255',
				'phptype' => 'string',
				'null' => false,
			],
			'type' => [
				'dbtype' => 'varchar',
				'precision' => '50',
				'phptype' => 'string',
				'null' => true,
			],
			'size' => [
				'dbtype' => 'int',
				'precision' => '10',
				'attributes' => 'unsigned',
				'phptype' => 'integer',
				'null' => false,
				'default' => 0,
			],
			'createdon' => [
				'dbtype' => 'datetime',
				'phptype' => 'datetime',
				'null' => true,
			],
			'createdby' => [
				'dbtype' => 'int',
				'precision' => '10',
				'attributes' => 'unsigned',
				'phptype' => 'integer',
				'null' => false,
				'default' => 0,
			],
			'rank' => [
				'dbtype' => 'tinyint',
				'precision' => '1',
				'attributes' => 'unsigned',
				'phptype' => 'integer',
				'null' => true,
				'default' => 0,
			],
			'url' => [
				'dbtype' => 'varchar',
				'precision' => '255',
				'phptype' => 'string',
				'null' => false,
			],
			'thumb' => [
				'dbtype' => 'varchar',
				'precision' => '255',
				'phptype' => 'string',
				'null' => false,
			],
			'thumbs' => [
				'dbtype' => 'text',
				'phptype' => 'json',
				'null' => true,
			],
			'deleted' => [
				'dbtype' => 'tinyint',
				'precision' => '1',
				'phptype' => 'boolean',
				'null' => true,
				'default' => 0,
			],
			'properties' => [
				'dbtype' => 'text',
				'phptype' => 'json',
				'null' => true,
			],
			'hash' => [
				'dbtype' => 'char',
				'precision' => '40',
				'phptype' => 'string',
				'null' => true,
				'default' => '',
				'index' => 'index',
			],
		],
		'indexes' => [
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
					'class' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'source' => [
				'alias' => 'source',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'source' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
			'type' => [
				'alias' => 'type',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'type' => [
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
			'hash' => [
				'alias' => 'hash',
				'primary' => false,
				'unique' => false,
				'type' => 'BTREE',
				'columns' => [
					'hash' => [
						'length' => '',
						'collation' => 'A',
						'null' => false,
					],
				],
			],
		],
	];
}
