<?php

use Tickets\Tickets;
use MODX\Revolution\modX;
use MODX\Revolution\modContext;
use Tickets\Model\TicketThread;
use MODX\Revolution\modResource;
use MODX\Revolution\Error\modError;

if (empty($_REQUEST['action'])) {
	exit('Access denied');
} else {
	$action = $_REQUEST['action'];
}

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
$modx = new modX();
$modx->initialize();

if (!isset($modx)) exit('Access denied');
if (!($modx instanceof modX)) exit('Access denied');

if (!$modx->services->has('error')) {
	$modx->services->add('error', function ($c) use ($modx) {
		return new \MODX\Revolution\Error\modError($modx);
	});
}
$modx->getRequest();
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');

// Get properties
$properties = [];
/** @var TicketThread $thread */
if (!empty($_REQUEST['thread']) && $thread = $modx->getObject(TicketThread::class, ['name' => $_REQUEST['thread']])) {
	$properties = $thread->get('properties');
	if (!empty($_REQUEST['form_key']) && isset($_SESSION['TicketForm'][$_REQUEST['form_key']])) {
		$properties = \array_merge($_SESSION['TicketForm'][$_REQUEST['form_key']], $properties);
	}
} elseif (!empty($_REQUEST['form_key']) && isset($_SESSION['TicketForm'][$_REQUEST['form_key']])) {
	$properties = $_SESSION['TicketForm'][$_REQUEST['form_key']];
} elseif (!empty($_SESSION['TicketForm'])) {
	$properties = $_SESSION['TicketForm'];
}

// Switch context
$context = 'web';
if (!empty($thread) && $thread->get('resource') && $object = $thread->getOne('Resource')) {
	$context = $object->get('context_key');
} elseif (!empty($_REQUEST['section']) && $object = $modx->getObject(modResource::class, (int) $_REQUEST['section'])) {
	$context = $object->get('context_key');
} elseif (!empty($_REQUEST['parent']) && $object = $modx->getObject(modResource::class, (int) $_REQUEST['parent'])) {
	$context = $object->get('context_key');
} elseif (!empty($_REQUEST['ctx']) && $object = $modx->getObject(modContext::class, ['key' => $_REQUEST['ctx']])) {
	$context = $object->get('key');
}
if ('web' != $context) {
	$modx->switchContext($context);
}

/** @var Tickets $Tickets */
\define('MODX_ACTION_MODE', true);
$Tickets = \tickets_service($modx, $properties);

switch ($action) {
	case 'comment/preview':
		$response = $Tickets->previewComment($_POST);
		break;
	case 'comment/save':
		$response = $Tickets->saveComment($_POST);
		break;
	case 'comment/get':
		$response = $Tickets->getComment((int) $_POST['id']);
		break;
	case 'comment/getlist':
		$response = $Tickets->getNewComments($_POST['thread']);
		break;
	case 'comment/subscribe':
		$response = $Tickets->subscribeThread($_POST['thread']);
		break;
	case 'comment/vote':
		$response = $Tickets->voteComment((int) $_POST['id'], (int) $_POST['value']);
		break;
	case 'comment/star':
		$response = $Tickets->starComment((int) $_POST['id']);
		break;
	case 'comment/file/upload':
		$response = $Tickets->fileUploadComment($_POST, 'TicketComment');
		break;

	case 'ticket/draft':
	case 'ticket/publish':
	case 'ticket/update':
	case 'ticket/save':
		$response = $Tickets->saveTicket($_POST);
		break;
	case 'ticket/preview':
		$response = $Tickets->previewTicket($_POST);
		break;
	case 'ticket/vote':
		$response = $Tickets->voteTicket((int) $_POST['id'], (int) $_POST['value']);
		break;
	case 'ticket/star':
		$response = $Tickets->starTicket((int) $_POST['id']);
		break;
	case 'ticket/delete':
		$response = $Tickets->deleteTicket(['id' => (int) $_POST['tid']]);
		break;
	case 'ticket/undelete':
		$response = $Tickets->deleteTicket(['id' => (int) $_POST['tid']], true);
		break;

	case 'section/subscribe':
		$response = $Tickets->subscribeSection((int) $_POST['section']);
		break;
	case 'author/subscribe':
		$response = $Tickets->subscribeAuthor((int) $_POST['author']);
		break;

	case 'ticket/file/upload':
		$response = $Tickets->fileUpload($_POST, 'Ticket');
		break;
	case 'ticket/file/delete':
		$response = $Tickets->fileDelete((int) $_POST['id']);
		break;
	case 'ticket/file/sort':
		$response = $Tickets->fileSort($_POST['rank']);
		break;
	default:
		$message = $_REQUEST['action'] != $action ? 'tickets_err_register_globals' : 'tickets_err_unknown';
		$response = \json_encode([
			'success' => false,
			'message' => $modx->lexicon($message),
		]);
}

if (\is_array($response)) {
	$response = \json_encode($response);
}

@\session_write_close();
exit($response);
