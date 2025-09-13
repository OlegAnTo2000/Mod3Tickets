<?php

use MODX\Revolution\modX;
use Tickets\Tickets;

if (!\function_exists('tickets_service')) {
	function tickets_service($modx = null, $config = []): Tickets
	{
		if (null === $modx) {
			$modx = modX::getInstance('web');
		}

		if (null === $modx || !($modx instanceof modX)) {
			throw new Exception('modX instance is required');
		}

		$tickets = $modx->services->get('tickets');
		if ($tickets instanceof Tickets) {
			return $tickets;
		}

		$modelPath = $modx->getOption('tickets.core_path', null, $modx->getOption('core_path') . 'components/tickets/') . 'src/Model/';
		$modx->addPackage('Tickets\Model', $modelPath, null, 'Tickets\\');
		$modx->services->add('tickets', function ($c) use ($modx, $config) {
			return new Tickets($modx, $config);
		});

		return $modx->services->get('tickets');
	}
}

$modx->map[\MODX\Revolution\modUser::class]['composites']['AuthorProfile'] = [
	'class'       => \Tickets\Model\TicketAuthor::class,
	'local'       => 'id',
	'foreign'     => 'id',
	'cardinality' => 'one',
	'owner'       => 'local',
];
