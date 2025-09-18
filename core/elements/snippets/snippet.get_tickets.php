<?php

use Tickets\Tickets;
use Tickets\Model\Ticket;
use ModxPro\PdoTools\Fetch;
use MODX\Revolution\modUser;
use Tickets\Model\TicketStar;
use Tickets\Model\TicketView;
use Tickets\Model\TicketVote;
use Tickets\Model\TicketTotal;
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

if (isset($parents) && '' === $parents) {
	$scriptProperties['parents'] = $modx->resource->id;
}

$class = Ticket::class;
$where = ['class_key' => $class];

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
	} else {
		if (!empty($user_id)) {
			$where['User.id:IN'] = $user_id;
		} else {
			if (!empty($user_username)) {
				$where['User.username:IN'] = $user_username;
			}
		}
	}
}

// Joining tables
$leftJoin = [
	'Section' => ['class' => TicketsSection::class, 'on' => '`Section`.`id` = `Ticket`.`parent`'],
	'User'    => ['class' => modUser::class, 'on' => '`User`.`id` = `Ticket`.`createdby`'],
	'Profile' => ['class' => modUserProfile::class, 'on' => '`Profile`.`internalKey` = `User`.`id`'],
	'Total'   => ['class' => TicketTotal::class],
];
if ($Tickets->authenticated) {
	$leftJoin['Vote'] = [
		'class' => TicketVote::class,
		'on'    => '`Vote`.`id` = `Ticket`.`id` AND `Vote`.`class` = "Ticket" AND `Vote`.`createdby` = ' . $modx->user->id,
	];
	$leftJoin['Star'] = [
		'class' => TicketStar::class,
		'on'    => '`Star`.`id` = `Ticket`.`id` AND `Star`.`class` = "Ticket" AND `Star`.`createdby` = ' . $modx->user->id,
	];
	$leftJoin['Thread'] = [
		'class' => TicketThread::class,
		'on'    => '`Thread`.`resource` = `Ticket`.`id` AND `Thread`.`deleted` = 0',
	];
}

// Fields to select
$select = [
	'Section' => $modx->getSelectColumns(TicketsSection::class, 'Section', 'section.', ['content'], true),
	'User'    => $modx->getSelectColumns(modUser::class, 'User', '', ['username']),
	'Profile' => $modx->getSelectColumns(modUserProfile::class, 'Profile', '', ['id'], true),
	'Ticket'  => !empty($includeContent)
		? $modx->getSelectColumns(Ticket::class, 'Ticket')
		: $modx->getSelectColumns(Ticket::class, 'Ticket', '', ['content'], true),
	'Total' => 'comments, views, stars, rating, rating_plus, rating_minus',
];
if ($Tickets->authenticated) {
	$select['Vote']   = '`Vote`.`value` as `vote`';
	$select['Star']   = 'COUNT(`Star`.`id`) as `star`';
	$select['Thread'] = '`Thread`.`id` as `thread`';
}
$pdoFetch->addTime('Conditions prepared');

// Add custom parameters
foreach (['where', 'select', 'leftJoin'] as $v) {
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

$default = [
	'class'    => $class,
	'where'    => \json_encode($where),
	'leftJoin' => \json_encode($leftJoin),
	'select'   => \json_encode($select),
	'sortby'   => 'createdon',
	'sortdir'  => 'DESC',
	'groupby'  => $class . '.id',
	'return'   => !empty($returnIds) ? 'ids' : 'data',
	'nestedChunkPrefix' => 'tickets_',
];

// Merge all properties and run!
$pdoFetch->setConfig(\array_merge($default, $scriptProperties));
$pdoFetch->addTime('Query parameters are prepared.');
$rows = $pdoFetch->run();

if (!empty($returnIds)) {
	return $rows;
}
\ini_set('error_reporting', -1);
\ini_set('display_errors', 1);
// Processing rows
$output = [];
if (!empty($rows) && \is_array($rows)) {
	foreach ($rows as $k => $row) {
		// Handle properties
		$properties = \is_string($row['properties'])
			? \json_decode($row['properties'], true)
			: $row['properties'];
		if (!empty($properties['tickets'])) {
			$properties = $properties['tickets'];
		}
		if (empty($properties['process_tags'])) {
			foreach ($row as $field => $value) {
				$row[$field] = \str_replace(
					['[', ']', '`', '{', '}'],
					['&#91;', '&#93;', '&#96;', '&#123;', '&#125;'],
					$value
				);
			}
		}
		if (!\is_array($properties)) {
			$properties = [];
		}

		// Handle rating
		if ($row['rating'] > 0) {
			$row['rating']          = '+' . $row['rating'];
			$row['rating_positive'] = 1;
		} elseif ($row['rating'] < 0) {
			$row['rating_negative'] = 1;
		}
		$row['rating_total'] = \abs($row['rating_plus']) + \abs($row['rating_minus']);
		// Handle rating
		if (isset($row['section.properties']['ratings']['days_ticket_vote'])) {
			if ('' !== $row['section.properties']['ratings']['days_ticket_vote']) {
				$max = $row['createdon'] + ((float) $row['section.properties']['ratings']['days_ticket_vote'] * 86400);
				if (\time() > $max) {
					$row['cant_vote'] = 1;
				}
			}
		}
		if (!isset($row['cant_vote'])) {
			if (!$Tickets->authenticated || $modx->user->id == $row['createdby']) {
				$row['cant_vote'] = 1;
			} elseif (\array_key_exists('vote', $row)) {
				if ('' == $row['vote']) {
					$row['can_vote'] = 1;
				} elseif ($row['vote'] > 0) {
					$row['voted_plus'] = 1;
					$row['cant_vote']  = 1;
				} elseif ($row['vote'] < 0) {
					$row['voted_minus'] = 1;
					$row['cant_vote']   = 1;
				} else {
					$row['voted_none'] = 1;
					$row['cant_vote']  = 1;
				}
			}
		}
		// Special fields for quick placeholders
		$row['active']      = (int) !empty($row['can_vote']);
		$row['inactive']    = (int) !empty($row['cant_vote']);
		$row['can_star']    = $Tickets->authenticated;
		$row['stared']      = !empty($row['star']);
		$row['unstared']    = empty($row['star']);
		$row['isauthor']    = $modx->user->id == $row['createdby'];
		$row['unpublished'] = empty($row['published']);

		$row['date_ago'] = $Tickets->dateFormat($row['createdon']);
		$row['idx']      = $pdoFetch->idx++;
		// Processing new comments
		if ($Tickets->authenticated && !empty($row['thread'])) {
			$last_view = $pdoFetch->getObject(TicketView::class, [
				'parent' => $row['id'],
				'uid'    => $modx->user->id,
			], [
				'sortby'  => 'timestamp',
				'sortdir' => 'DESC',
				'limit'   => 1,
			]);
			if (!empty($last_view['timestamp'])) {
				$row['new_comments'] = $modx->getCount(TicketComment::class, [
					'published'    => 1,
					'thread'       => $row['thread'],
					'createdon:>'  => $last_view['timestamp'],
					'createdby:!=' => $modx->user->id,
				]);
			} else {
				$row['new_comments'] = $row['comments'];
			}
		} else {
			$row['new_comments'] = '';
		}

		// Processing chunk
		$tpl      = $pdoFetch->defineChunk($row);
		$output[] = empty($tpl)
			? '<pre>' . $pdoFetch->getChunk('', $row) . '</pre>'
			: $pdoFetch->getChunk($tpl, $row, $pdoFetch->config('fastMode'));
	}
}
$pdoFetch->addTime('Returning processed chunks');

$log = '';
if ($modx->user->hasSessionContext('mgr') && !empty($showLog)) {
	$log .= '<pre class="getTicketsLog">' . \print_r($pdoFetch->getTime(), 1) . '</pre>';
}

// Return output
if (!empty($toSeparatePlaceholders)) {
	$output['log'] = $log;
	$modx->setPlaceholders($output, $toSeparatePlaceholders);
} else {
	if (empty($outputSeparator)) {
		$outputSeparator = "\n";
	}
	$output = \implode($outputSeparator, $output);
	$output .= $log;

	if (!empty($tplWrapper) && (!empty($wrapIfEmpty) || !empty($output))) {
		$array = ['output' => $output];
		if ($Tickets->authenticated && TicketsSection::class == $modx->resource->class_key) {
			/** @var TicketsSection $section */
			$section             = &$modx->resource;
			$array['subscribed'] = $section->isSubscribed();
		}
		$output = $pdoFetch->getChunk($tplWrapper, $array, $pdoFetch->config('fastMode'));
	}

	if (!empty($toPlaceholder)) {
		$modx->setPlaceholder($toPlaceholder, $output);
	} else {
		return $output;
	}
}
