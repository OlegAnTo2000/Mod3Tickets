<?php

$xpdo_meta_map = [
	'version'         => '3.0',
	'namespace'       => 'Tickets\\Model',
	'namespacePrefix' => 'Tickets',
	'class_map'       => [
		'MODX\\Revolution\\modResource' => [
			0 => 'Tickets\\Model\\TicketsSection',
			1 => 'Tickets\\Model\\Ticket',
		],
		'xPDO\\Om\\xPDOSimpleObject' => [
			0 => 'Tickets\\Model\\TicketComment',
			1 => 'Tickets\\Model\\TicketThread',
			2 => 'Tickets\\Model\\TicketQueue',
			3 => 'Tickets\\Model\\TicketFile',
		],
		'xPDO\\Om\\xPDOObject' => [
			0 => 'Tickets\\Model\\TicketVote',
			1 => 'Tickets\\Model\\TicketStar',
			2 => 'Tickets\\Model\\TicketView',
			3 => 'Tickets\\Model\\TicketAuthor',
			4 => 'Tickets\\Model\\TicketAuthorAction',
			5 => 'Tickets\\Model\\TicketTotal',
		],
	],
];
