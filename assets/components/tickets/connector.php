<?php

/** @noinspection PhpIncludeInspection */
require_once \dirname(\dirname(\dirname(\dirname(__FILE__)))) . '/config.core.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';

use Tickets\Tickets;

/** @var Tickets $Tickets */
$Tickets = $modx->getService('tickets', 'Tickets', MODX_CORE_PATH . 'components/tickets/model/tickets/');
$modx->lexicon->load('tickets:default');

/** @var modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest([
	'processors_path' => $modx->getOption('processorsPath', $Tickets->config, MODX_CORE_PATH . '/components/tickets/src/Processors/'),
	'location'        => '',
]);
