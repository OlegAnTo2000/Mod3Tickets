<?php

namespace Tickets\Model\mysql;

class Ticket extends \Tickets\Model\Ticket
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
			'Views' => [
				'class' => 'TicketView',
				'local' => 'id',
				'foreign' => 'parent',
				'cardinality' => 'many',
				'owner' => 'local',
			],
			'Votes' => [
				'class' => 'TicketVote',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'many',
				'owner' => 'local',
				'criteria' => [
					'foreign' => [
						'class' => 'Ticket',
					],
				],
			],
			'Stars' => [
				'class' => 'TicketStar',
				'local' => 'id',
				'foreign' => 'id',
				'cardinality' => 'many',
				'owner' => 'local',
				'criteria' => [
					'foreign' => [
						'class' => 'Ticket',
					],
				],
			],
			'Files' => [
				'class' => 'TicketFile',
				'local' => 'id',
				'foreign' => 'parent',
				'cardinality' => 'many',
				'owner' => 'local',
				'criteria' => [
					'foreign' => [
						'class' => 'Ticket',
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
						'class' => 'Ticket',
					],
				],
			],
		],
		'aggregates' => [
			'Section' => [
				'class' => 'TicketsSection',
				'local' => 'parent',
				'foreign' => 'id',
				'cardinality' => 'one',
				'owner' => 'foreign',
			],
			'Threads' => [
				'class' => 'TicketThread',
				'local' => 'id',
				'foreign' => 'resource',
				'cardinality' => 'many',
				'owner' => 'local',
			],
		],
	];
}
