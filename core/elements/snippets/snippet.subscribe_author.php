<?php

use Tickets\Tickets;
use Tickets\Model\TicketAuthor;

/** @var \MODX\Revolution\modX $modx */
/** @var array $scriptProperties */
/** @var Tickets $Tickets */
$Tickets = \tickets_service($modx, $scriptProperties);

if (!$Tickets->authenticated || empty($scriptProperties['createdby'])) {
	return '';
}

if (!empty($scriptProperties['TicketsInit'])) {
	$Tickets->initialize($modx->context->key, $scriptProperties);
}

if ($profile = $modx->getObject(TicketAuthor::class, ['id' => $scriptProperties['createdby']])) {
	$properties = $profile->get('properties');
	if (!empty($properties['subscribers'])) {
		$found      = \array_search($modx->user->id, $properties['subscribers'], true);
		$subscribed = (false === $found) ? 0 : 1;
	}
}

$tpl  = $modx->getOption('tpl', $scriptProperties, 'tpl.Tickets.author.subscribe');
$data = [
	'author_id'  => $scriptProperties['createdby'],
	'subscribed' => $subscribed,
];
$output = $Tickets->getChunk($tpl, $data);

// Return output
if (!empty($toPlaceholder)) {
	$modx->setPlaceholder($toPlaceholder, $output);
} else {
	return $output;
}
