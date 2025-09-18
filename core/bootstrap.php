<?php

use MODX\Revolution\modX;
use Tickets\Tickets;

if (!defined('MODX_CORE_PATH')) {
	if (file_exists('/modx/config.core.php')) {
		require '/modx/config.core.php'; // for local development
	} else {
		$dir = __DIR__;
		while (true) {
			if ($dir === '/') break;
			if (file_exists($dir . '/config.core.php')) {
				require $dir . '/config.core.php';
				break;
			}
			if (file_exists($dir . '/config/config.inc.php')) {
				require $dir . '/config/config.inc.php';
				break;
			}
			$dir = dirname($dir);
		}
	}
	if (!defined('MODX_CORE_PATH')) {
		exit('Could not load MODX core');
	}
	require MODX_CORE_PATH . '/vendor/autoload.php';
}

if (!isset($modx)) {
	$modx = new modX();
	$modx->initialize();
}

if (!\function_exists('tickets_service')) {
	function tickets_service($modx = null, $config = []): Tickets
	{
		if (null === $modx) $modx = modX::getInstance('web');

		if (null === $modx || !($modx instanceof modX)) {
			throw new Exception('modX instance is required');
		}

		if ($modx->services->has('tickets') && $modx->services->get('tickets') instanceof Tickets) {
			return $modx->services->get('tickets');
		}

		$modelPath = $modx->getOption('tickets.core_path', null, $modx->getOption('core_path') . 'components/tickets/') . 'src/Model/';
		$modx->addPackage('Tickets\Model', $modelPath, null, 'Tickets\\');
		$modx->lexicon->load('tickets:default');
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
