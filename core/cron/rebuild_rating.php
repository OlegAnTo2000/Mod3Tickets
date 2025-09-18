<?php

use MODX\Revolution\modX;
use MODX\Revolution\modUser;
use Tickets\Model\TicketTotal;
use Tickets\Model\TicketAuthor;
use Tickets\Model\TicketAuthorAction;

\define('MODX_API_MODE', true);

/** @noinspection PhpIncludeInspection */
require_once \dirname(\dirname(\dirname(\dirname(\dirname(__FILE__))))) . '/index.php';
/** @var modX $modx */
if (!$modx->services->has('error')) {
	$modx->services->add('error', function ($c) use ($modx) {
		return new \MODX\Revolution\Error\modError($modx);
	});
}
$modx->getRequest();
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');
$modx->error->message = null;

$time = \time();

$modx->removeCollection(TicketAuthorAction::class, []);
$modx->removeCollection(TicketTotal::class, []);

$c = $modx->newQuery(modUser::class);
$c->sortby('id', 'asc');
$users = $modx->getIterator(modUser::class, $c);
/** @var modUser $user */
foreach ($users as $user) {
	/** @var TicketAuthor $profile */
	$profile = $user->getOne('AuthorProfile');
	if (!$profile) {
		$profile = $modx->newObject(TicketAuthor::class);
		$user->addOne($profile);
	}
	$profile->refreshActions(true, true);
	$profile->save();
}

echo 'Done in ' . (\time() - $time) . " sec.\n\n";
