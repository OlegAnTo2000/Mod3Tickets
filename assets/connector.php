<?php

\define('MODX_API_MODE', true);

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
		exit('Access denied');
	}
	require_once MODX_CORE_PATH . '/vendor/autoload.php';
}

/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';

use Tickets\Tickets;

/** @var Tickets $Tickets */
$tickets = tickets_service($modx);

/** @var \MODX\Revolution\modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest([
	'processors_path' => $modx->getOption('processorsPath', $tickets->config, MODX_CORE_PATH . '/components/tickets/src/Processors/'),
	'location'        => '',
]);
