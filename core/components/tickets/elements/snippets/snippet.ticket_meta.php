<?php

/** @var array $scriptProperties */
/** @var Tickets $Tickets */
$Tickets = $modx->getService('tickets', 'Tickets', $modx->getOption(
	'tickets.core_path',
	null,
	$modx->getOption('core_path') . 'components/tickets/'
) . 'model/tickets/', $scriptProperties);
$Tickets->initialize($modx->context->key, $scriptProperties);

$scriptProperties['nestedChunkPrefix'] = 'tickets_';
/** @var pdoFetch $pdoFetch */
$pdoFetch = $modx->getService('pdoFetch');
$pdoFetch->setConfig($scriptProperties);
$pdoFetch->addTime('pdoTools loaded');

if (empty($id)) {
	$id = $modx->resource->id;
}
/** @var Ticket|modResource $ticket */
if (!$ticket = $modx->getObject(modResource::class, ['id' => $id])) {
	return 'Could not load resource with id = ' . $id;
}

$class = $ticket instanceof Ticket
	? 'Ticket'
	: 'modResource';

/** @var TicketTotal $total */
if ('Ticket' == $class && $total = $ticket->getOne('Total')) {
	$total->fetchValues();
	$total->save();
}
$data = $ticket->toArray();
$data['date_ago'] = $Tickets->dateFormat($data['createdon']);

$vote = $pdoFetch->getObject(
	TicketVote::class,
	[
		'id' => $ticket->id,
		'class' => 'Ticket',
		'createdby' => $modx->user->id,
	],
	[
		'select' => 'value',
		'sortby' => 'id',
	]
);
if (!empty($vote)) {
	$data['vote'] = $vote['value'];
}

$star = $modx->getCount('TicketStar', ['id' => $ticket->id, 'class' => 'Ticket', 'createdby' => $modx->user->id]);
$data['stared'] = !empty($star);
$data['unstared'] = empty($star);

if ('Ticket' != $class) {
	// Rating
	if (!$modx->user->id || $modx->user->id == $ticket->createdby) {
		$data['voted'] = 0;
	} else {
		$q = $modx->newQuery('TicketVote');
		$q->where(['id' => $ticket->id, 'createdby' => $modx->user->id, 'class' => 'Ticket']);
		$q->select('`value`');
		$tstart = \microtime(true);
		if ($q->prepare() && $q->stmt->execute()) {
			$modx->startTime += \microtime(true) - $tstart;
			++$modx->executedQueries;
			$voted = $q->stmt->fetchColumn();
			if ($voted > 0) {
				$voted = 1;
			} elseif ($voted < 0) {
				$voted = -1;
			}
			$data['voted'] = $voted;
		}
	}
	$data['can_vote'] = false === $data['voted'] && $Tickets->authenticated && $modx->user->id != $ticket->createdby;
	$data = \array_merge($ticket->getProperties('tickets'), $data);
	if (!isset($data['rating'])) {
		$data['rating'] = $data['rating_total'] = $data['rating_plus'] = $data['rating_minus'] = 0;
	}

	// Views
	$data['views'] = $modx->getCount('TicketView', ['parent' => $ticket->id]);

	// Comments
	$data['comments'] = 0;
	$thread = empty($thread)
		? 'resource-' . $ticket->id
		: $thread;
	$q = $modx->newQuery('TicketThread', ['name' => $thread]);
	$q->leftJoin(
		'TicketComment',
		'TicketComment',
		'TicketThread.id = TicketComment.thread AND TicketComment.published = 1'
	);
	$q->select('COUNT(`TicketComment`.`id`) as `comments`');
	$tstart = \microtime(true);
	if ($q->prepare() && $q->stmt->execute()) {
		$modx->startTime += \microtime(true) - $tstart;
		++$modx->executedQueries;
		$data['comments'] = (int) $q->stmt->fetchColumn();
	}

	// Stars
	$data['stars'] = $modx->getCount('TicketStar', ['id' => $ticket->id, 'class' => 'Ticket']);
}

if ($data['rating'] > 0) {
	$data['rating'] = '+' . $data['rating'];
	$data['rating_positive'] = 1;
} elseif ($data['rating'] < 0) {
	$data['rating_negative'] = 1;
}
$data['rating_total'] = \abs($data['rating_plus']) + \abs($data['rating_minus']);

/** @var TicketsSection $section */
if ($section = $modx->getObject(TicketsSection::class, ['id' => $ticket->parent])) {
	$data = \array_merge($data, $section->toArray('section.'));
}
if (isset($data['section.properties']['ratings']['days_ticket_vote'])) {
	if ('' !== $data['section.properties']['ratings']['days_ticket_vote']) {
		$max = \strtotime($data['createdon']) + ((float) $data['section.properties']['ratings']['days_ticket_vote'] * 86400);
		if (\time() > $max) {
			$data['cant_vote'] = 1;
		}
	}
}
if (!isset($data['cant_vote'])) {
	if (!$Tickets->authenticated || $modx->user->id == $ticket->createdby) {
		$data['cant_vote'] = 1;
	} elseif (\array_key_exists('vote', $data)) {
		if ('' == $data['vote']) {
			$data['can_vote'] = 1;
		} elseif ($data['vote'] > 0) {
			$data['voted_plus'] = 1;
			$data['cant_vote'] = 1;
		} elseif ($data['vote'] < 0) {
			$data['voted_minus'] = 1;
			$data['cant_vote'] = 1;
		} else {
			$data['voted_none'] = 1;
			$data['cant_vote'] = 1;
		}
	} else {
		$data['can_vote'] = 1;
	}
}

$data['active'] = (int) !empty($data['can_vote']);
$data['inactive'] = (int) !empty($data['cant_vote']);
$data['can_star'] = $Tickets->authenticated;

if (!empty($getUser)) {
	$fields = $modx->getFieldMeta('modUserProfile');
	$user = $pdoFetch->getObject('modUserProfile', ['internalKey' => $ticket->createdby], [
		'innerJoin' => [
			'modUser' => ['class' => 'modUser', 'on' => 'modUserProfile.internalKey = modUser.id'],
		],
		'select' => [
			'modUserProfile' => \implode(',', \array_keys($fields)),
			'modUser' => 'username',
		],
	]);
	if ($user) {
		$data = \array_merge($data, $user);
	}
}

if (!empty($getFiles)) {
	$where = ['deleted' => 0, 'class' => 'Ticket', 'parent' => $ticket->id];
	$collection = $pdoFetch->getCollection('TicketFile', $where, ['sortby' => 'createdon', 'sortdir' => 'ASC']);
	$data['files'] = $content = '';
	if (!empty($unusedFiles)) {
		$content = $ticket->getContent();
	}
	foreach ($collection as $item) {
		if ($content && false !== \strpos($content, $item['url'])) {
			continue;
		}
		$item['size'] = \round($item['size'] / 1024, 2);
		$data['files'] .= !empty($tplFile)
			? $Tickets->getChunk($tplFile, $item)
			: $Tickets->getChunk('', $item);
	}
	$data['has_files'] = !empty($data['files']);
}
$data['id'] = $ticket->get('id');

return !empty($tpl)
	? $Tickets->getChunk($tpl, $data)
	: $Tickets->getChunk('', $data);
