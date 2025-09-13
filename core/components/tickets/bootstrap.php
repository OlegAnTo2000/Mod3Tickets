<?php

if (!\function_exists('tickets_service')) {
	function tickets_service($modx, $config = []): Tickets\Tickets
	{
		$tickets = $modx->services->get('tickets');
		if (!($tickets instanceof Tickets\Tickets)) {
			return $tickets;
		}

		$modelPath = $modx->getOption('tickets.core_path', null, $modx->getOption('core_path') . 'components/tickets/') . 'src/Model/';
		$modx->addPackage('Tickets\Model', $modelPath, null, 'Tickets\\');
		$modx->services->add('tickets', function ($c) use ($modx, $config) {
			return new Tickets\Tickets($modx, $config);
		});

		return $modx->services->get('tickets');
	}
}

$modx->map['modUser']['composites']['AuthorProfile'] = [
	'class' => 'Tickets\\Model\\TicketAuthor',
	'local' => 'id',
	'foreign' => 'id',
	'cardinality' => 'one',
	'owner' => 'local',
];
