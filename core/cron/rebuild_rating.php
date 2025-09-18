<?php

use MODX\Revolution\modX;
use MODX\Revolution\modUser;
use Tickets\Model\TicketAuthor;
use MODX\Revolution\Error\modError;

\define('MODX_API_MODE', true);

/** @noinspection PhpIncludeInspection */
require_once \dirname(\dirname(\dirname(\dirname(\dirname(__FILE__))))) . '/index.php';
/** @var modX $modx */
$modx->services->add('error', modError::class);
$modx->getRequest();
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');
$modx->error->message = null;

$time = \time();

$modx->removeCollection('TicketAuthorAction', []);
$modx->removeCollection('TicketTotal', []);

$c = $modx->newQuery(modUser::class);
$c->sortby('id', 'asc');
$users = $modx->getIterator(modUser::class, $c);
/** @var modUser $user */
foreach ($users as $user) {
	/** @var TicketAuthor $profile */
	if (!$profile = $user->getOne('AuthorProfile')) {
		$profile = $modx->newObject(TicketAuthor::class);
		$user->addOne($profile);
	}
	$profile->refreshActions(true, true);
	$profile->save();
}

echo 'Done in ' . (\time() - $time) . " sec.\n\n";
