<?php

namespace Tickets\Model\mysql;

class TicketsSection extends \Tickets\Model\TicketsSection
{
	public static $metaMap = [
		'package' => 'Tickets\\Model\\',
		'version' => '3.0',
		'extends' => 'MODX\\Revolution\\modResource',
		'tableMeta' => [
			'engine' => 'InnoDB',
		],
		'fields' => [
		],
		'fieldMeta' => [
		],
		'composites' => [
			'Tickets' => [
				'class' => 'Ticket',
				'local' => 'id',
				'foreign' => 'parent',
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
						'class' => 'TicketsSection',
					],
				],
			],
		],
	];
}
