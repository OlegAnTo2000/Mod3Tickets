<?php

use Tickets\Tickets;
use Tickets\Model\Ticket;
use MODX\Revolution\modUser;
use Tickets\Model\TicketStar;
use Tickets\Model\TicketVote;
use Tickets\Model\TicketThread;
use Tickets\Model\TicketComment;
use Tickets\Model\TicketsSection;
use MODX\Revolution\modUserProfile;

/** @var \MODX\Revolution\modX $modx */
/** @var array $scriptProperties */
/** @var Tickets $Tickets */
$Tickets = \tickets_service($modx, $scriptProperties);
$Tickets->initialize($modx->context->key, $scriptProperties);

/** @var \ModxPro\PdoTools\Fetch $pdoFetch */
$pdoFetch = $modx->services->get('pdoFetch');
$pdoFetch->setConfig($scriptProperties);
$pdoFetch->addTime('pdoTools loaded');

$tpl             = $modx->getOption('tpl', $scriptProperties, 'tpl.Tickets.comment.list.row');
$outputSeparator = $modx->getOption('outputSeparator', $scriptProperties, "\n");

// Define threads of comments
if (!empty($parents) || !empty($resources) || !empty($threads)) {
	$where   = [];
	$options = [
		'innerJoin' => [
			'Thread' => [
				'class' => TicketThread::class,
				'on'    => '`Ticket`.`id` = `Thread`.`resource`',
			],
		],
		'groupby'         => '`Ticket`.`id`',
		'select'          => ['Thread' => '`Thread`.`id`'],
		'showUnpublished' => !empty($showUnpublished),
		'showDeleted'     => !empty($showDeleted),
		'depth'           => isset($depth) ? (int) $depth : 10,
	];
	if (!empty($parents)) {
		$options['parents'] = $parents;
	}
	if (!empty($resources)) {
		$options['resources'] = $resources;
	}
	if (!empty($threads)) {
		$threads    = \array_map('trim', \explode(',', $threads));
		$threads_in = $threads_out = [];
		foreach ($threads as $v) {
			if (!\is_numeric($v)) {
				continue;
			}
			if ('-' == $v[0]) {
				$threads_out[] = \abs($v);
			} else {
				$threads_in[] = \abs($v);
			}
		}
		if (!empty($threads_in)) {
			$where['Thread.id:IN'] = $threads_in;
		}
		if (!empty($threads_out)) {
			$where['Thread.id:NOT IN'] = $threads_out;
		}
	}

	$rows    = $pdoFetch->getCollection(Ticket::class, $where, $options);
	$threads = [];
	foreach ($rows as $item) {
		$threads[] = $item['id'];
	}
	if (!\count($threads)) {
		return;
	}
}

// Prepare query to db
$class = TicketComment::class;
$where = [];
if (empty($showUnpublished)) {
	$where['published'] = 1;
}
if (empty($showDeleted)) {
	$where['deleted'] = 0;
}

// Filter by user
if (!empty($user)) {
	$user    = \array_map('trim', \explode(',', $user));
	$user_id = $user_username = [];
	foreach ($user as $v) {
		if (\is_numeric($v)) {
			$user_id[] = $v;
		} else {
			$user_username[] = $v;
		}
	}
	if (!empty($user_id) && !empty($user_username)) {
		$where[] = '(`User`.`id` IN (' . \implode(',', $user_id) . ') OR `User`.`username` IN (\'' . \implode(
			'\',\'',
			$user_username
		) . '\'))';
	} elseif (!empty($user_id)) {
		$where['User.id:IN'] = $user_id;
	} elseif (!empty($user_username)) {
		$where['User.username:IN'] = $user_username;
	}
}
// Filter by threads
if (!empty($threads)) {
	$where['thread:IN'] = $threads;
}
// Filter by comments
if (!empty($comments)) {
	$comments    = \array_map('trim', \explode(',', $comments));
	$comments_in = $comments_out = [];
	foreach ($comments as $v) {
		if (!\is_numeric($v)) {
			continue;
		}
		if ('-' == $v[0]) {
			$comments_out[] = \abs($v);
		} else {
			$comments_in[] = \abs($v);
		}
	}
	if (!empty($comments_in)) {
		$where['id:IN'] = $comments_in;
	}
	if (!empty($comments_out)) {
		$where['id:NOT IN'] = $comments_out;
	}
}

// Joining tables
$innerJoin = [
	'Thread' => [
		'class' => TicketThread::class,
		'on'    => '`Thread`.`id` = `TicketComment`.`thread`',
	],
];
$leftJoin = [
	'User'    => ['class' => modUser::class, 'on' => '`User`.`id` = `TicketComment`.`createdby`'],
	'Profile' => ['class' => modUserProfile::class, 'on' => '`Profile`.`internalKey` = `TicketComment`.`createdby`'],
	'Ticket'  => ['class' => Ticket::class, 'on' => '`Ticket`.`id` = `Thread`.`resource`'],
	'Section' => ['class' => TicketsSection::class, 'on' => '`Section`.`id` = `Ticket`.`parent`'],
];
if ($Tickets->authenticated) {
	$leftJoin['Vote'] = [
		'class' => TicketVote::class,
		'on'    => '`Vote`.`id` = `TicketComment`.`id` AND `Vote`.`class` = "' . TicketComment::class . '" AND `Vote`.`createdby` = ' . $modx->user->id,
	];
	$leftJoin['Star'] = [
		'class' => TicketStar::class,
		'on'    => '`Star`.`id` = `TicketComment`.`id` AND `Star`.`class` = "' . TicketComment::class . '" AND `Star`.`createdby` = ' . $modx->user->id,
	];
}
// Fields to select
$select = [
	'TicketComment' => $modx->getSelectColumns(TicketComment::class, 'TicketComment', '', ['raw'], true),
	'Thread'        => '`Thread`.`resource`, `Thread`.`comments`',
	'User'          => '`User`.`username`',
	'Profile'       => $modx->getSelectColumns(
		modUserProfile::class,
		'Profile',
		'',
		['id', 'email'],
		true
	) . ', `Profile`.`email` as `user_email`',
	'Ticket' => !empty($includeContent)
		? $modx->getSelectColumns(Ticket::class, 'Ticket', 'ticket.')
		: $modx->getSelectColumns(Ticket::class, 'Ticket', 'ticket.', ['content'], true),
	'Section' => !empty($includeContent)
		? $modx->getSelectColumns(TicketsSection::class, 'Section', 'section.')
		: $modx->getSelectColumns(TicketsSection::class, 'Section', 'section.', ['content'], true),
];
if ($Tickets->authenticated) {
	$select['Vote'] = '`Vote`.`value` as `vote`';
	$select['Star'] = 'COUNT(`Star`.`id`) as `star`';
}

// Add custom parameters
foreach (['where', 'select', 'leftJoin', 'innerJoin'] as $v) {
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
$pdoFetch->addTime('Conditions prepared');

$default = [
	'class'             => $class,
	'where'             => \json_encode($where),
	'innerJoin'         => \json_encode($innerJoin),
	'leftJoin'          => \json_encode($leftJoin),
	'select'            => \json_encode($select),
	'sortby'            => $class . '.createdon',
	'sortdir'           => 'DESC',
	'groupby'           => $class . '.id',
	'fastMode'          => true,
	'return'            => 'data',
	'nestedChunkPrefix' => 'tickets_',
];

// Merge all properties and run!
$pdoFetch->setConfig(\array_merge($default, $scriptProperties), false);
$pdoFetch->addTime('Query parameters prepared.');
$rows = $pdoFetch->run();

$output = [];
if (!empty($rows)) {
	foreach ($rows as $row) {
		$row['ratings'] = !empty($row['section.properties']['ratings']) ? $row['section.properties']['ratings'] : [];
		$output[]       = $Tickets->templateNode($row, $tpl);
	}
}
$pdoFetch->addTime('Returning processed chunks');
$output = \implode($outputSeparator, $output);

$log = '';
if ($modx->user->hasSessionContext('mgr') && !empty($showLog)) {
	$log .= '<pre class="getCommentsLog">' . \print_r($pdoFetch->getTime(), 1) . '</pre>';
}

// Return output
if (!empty($toSeparatePlaceholders)) {
	$output['log'] = $log;
	$modx->setPlaceholders($output, $toSeparatePlaceholders);
} else {
	$output .= $log;

	if (!empty($tplWrapper) && (!empty($wrapIfEmpty) || !empty($output))) {
		$output = $pdoFetch->getChunk($tplWrapper, ['output' => $output], $pdoFetch->config('fastMode'));
	}

	if (!empty($toPlaceholder)) {
		$modx->setPlaceholder($toPlaceholder, $output);
	} else {
		return $output;
	}
}
