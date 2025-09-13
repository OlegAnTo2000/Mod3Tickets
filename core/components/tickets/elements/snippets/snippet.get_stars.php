<?php

/** @var array $scriptProperties */
if (empty($class)) {
	$class = 'Ticket';
}
/** @var int $user */
if (empty($user)) {
	$user = $modx->user->get('id');
}
unset($scriptProperties['user']);

$ids = [];
$q   = $modx->newQuery('TicketStar', ['class' => $class, 'createdby' => $user]);
$q->select('id');
$tstart = \microtime(true);
if ($q->prepare() && $q->stmt->execute()) {
	$modx->queryTime = \microtime(true) - $tstart;
	++$modx->executedQueries;

	$ids = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (empty($ids)) {
	return false;
}

$where = [$class . '.id:IN' => $ids];
foreach (['where'] as $v) {
	if (!empty($scriptProperties[$v])) {
		$tmp = $scriptProperties[$v];
		if (!\is_array($tmp)) {
			$tmp = \json_decode($tmp, true);
		}
		if (\is_array($tmp)) {
			$$v = \array_merge($$v, $tmp);
		}
	}
	unset($scriptProperties[$v]);
}
$scriptProperties['where'] = \json_encode($where);
if (empty($parents)) {
	$scriptProperties['parents'] = 0;
}
if (empty($tpl)) {
	unset($scriptProperties['tpl']);
}

return 'Ticket' == $class
	? $modx->runSnippet('getTickets', $scriptProperties)
	: $modx->runSnippet('getComments', $scriptProperties);
