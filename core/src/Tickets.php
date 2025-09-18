<?php

namespace Tickets;

use function abs;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_unique;
use function date;
use function defined;
use function explode;
use function file_exists;
use function html_entity_decode;
use function htmlentities;
use function in_array;
use function is_array;
use function is_object;
use function json_decode;
use function json_encode;
use function md5;
use function method_exists;
use function mktime;

use MODX\Revolution\modManagerController;
use MODX\Revolution\modResource;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modUser;
use MODX\Revolution\modUserProfile;
use MODX\Revolution\modX;
use MODX\Revolution\pdoTools;
use MODX\Revolution\Processors\ProcessorResponse;
use MODX\Revolution\Processors\Resource\Create as ResourceCreateProcessor;
use MODX\Revolution\Processors\Resource\Update as ResourceUpdateProcessor;

use function mt_rand;

use PDO;

use function preg_match;
use function preg_replace;
use function print_r;
use function round;
use function str_replace;
use function strpos;
use function strtolower;
use function strtotime;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Tickets\Model\Ticket;
use Tickets\Model\TicketAuthor;
use Tickets\Model\TicketComment;
use Tickets\Model\TicketQueue;
use Tickets\Model\TicketsSection;
use Tickets\Model\TicketThread;
use Tickets\Model\TicketView;
use Tickets\Processors\Web\Ticket\Delete as TicketDeleteProcessor;
use Tickets\Processors\Web\Ticket\Undelete as TicketUndeleteProcessor;
use Tickets\Processors\Web\Ticket\Vote as TicketVoteProcessor;

use function time;
use function trim;
use function version_compare;

class Tickets
{
	/** @var modX */
	public $modx;
	/** @var pdoFetch */
	public $pdoTools;
	public $initialized   = [];
	public $authenticated = false;
	private $prepareCommentCustom;
	private $last_view = 0;
	public $config     = [];

	public function __construct(modX &$modx, array $config = [])
	{
		$this->modx = &$modx;

		$corePath = $this->modx->getOption(
			'tickets.core_path',
			$config,
			$this->modx->getOption('core_path') . 'components/tickets/'
		);
		$assetsPath = $this->modx->getOption(
			'tickets.assets_path',
			$config,
			$this->modx->getOption('assets_path') . 'components/tickets/'
		);
		$assetsUrl = $this->modx->getOption(
			'tickets.assets_url',
			$config,
			$this->modx->getOption('assets_url') . 'components/tickets/'
		);
		$actionUrl    = $this->modx->getOption('tickets.action_url', $config, $assetsUrl . 'action.php');
		$connectorUrl = $assetsUrl . 'connector.php';

		$this->config = array_merge([
			'assetsUrl' => $assetsUrl,
			'cssUrl'    => $assetsUrl . 'css/',
			'jsUrl'     => $assetsUrl . 'js/',
			'jsPath'    => $assetsPath . 'js/',
			'imagesUrl' => $assetsUrl . 'img/',

			'connectorUrl' => $connectorUrl,
			'actionUrl'    => $actionUrl,

			'corePath'       => $corePath,
			'srcPath'        => $corePath . 'src/',
			'modelPath'      => $corePath . 'model/',
			'chunksPath'     => $corePath . 'elements/chunks/',
			'templatesPath'  => $corePath . 'elements/templates/',
			'chunkSuffix'    => '.chunk.tpl',
			'snippetsPath'   => $corePath . 'elements/snippets/',
			'processorsPath' => $corePath . 'Processors/',

			'fastMode'              => false,
			'dateFormat'            => 'd F Y, H:i',
			'dateNow'               => 10,
			'dateDay'               => 'day H:i',
			'dateMinutes'           => 59,
			'dateHours'             => 10,
			'charset'               => $this->modx->getOption('modx_charset'),
			'snippetPrepareComment' => $this->modx->getOption('tickets.snippet_prepare_comment'),
			'commentEditTime'       => $this->modx->getOption('tickets.comment_edit_time', null, 180),
			'depth'                 => 0,

			'gravatarUrl'  => 'https://www.gravatar.com/avatar/',
			'gravatarSize' => 24,
			'gravatarIcon' => 'mm',

			'json_response'     => true,
			'nestedChunkPrefix' => 'tickets_',
			'allowGuest'        => false,
			'allowGuestEdit'    => false,
			'allowGuestEmails'  => false,
			'enableCaptcha'     => false,

			'requiredFields' => '',
		], $config);

		$this->modx->addPackage('Tickets\Model', $this->config['srcPath'], null, 'Tickets\\');
		$this->modx->lexicon->load('tickets:default');

		if ($name = $this->config['snippetPrepareComment']) {
			if ($snippet = $this->modx->getObject(modSnippet::class, ['name' => $name])) {
				$this->prepareCommentCustom = $snippet->get('content');
			}
		}

		$this->authenticated = $this->modx->user->isAuthenticated($this->modx->context->get('key'));
	}

	/**
	 * Initializes component into different contexts.
	 *
	 * @param string $ctx              The context to load. Defaults to web.
	 * @param array  $scriptProperties
	 *
	 * @return bool
	 */
	public function initialize($ctx = 'web', $scriptProperties = [])
	{
		$this->config = array_merge($this->config, $scriptProperties);
		if (!$this->pdoTools) {
			$this->loadPdoTools();
		}
		$this->pdoTools->setConfig($this->config);

		$this->config['ctx'] = $ctx;
		if (empty($this->initialized[$ctx])) {
			$config_js = [
				'ctx'               => $ctx,
				'jsUrl'             => $this->config['jsUrl'] . 'web/',
				'cssUrl'            => $this->config['cssUrl'] . 'web/',
				'actionUrl'         => $this->config['actionUrl'],
				'close_all_message' => $this->modx->lexicon('tickets_message_close_all'),
				'tpanel'            => (int) $this->authenticated,
				'enable_editor'     => (int) $this->modx->getOption('tickets.enable_editor'),
			];
			$this->modx->regClientStartupScript(
				'<script type="text/javascript">if (typeof TicketsConfig == "undefined")  {TicketsConfig=' . json_encode($config_js) . ';} else {MergeConfig=' . json_encode($config_js) . '; for (var attrname in MergeConfig) { TicketsConfig[attrname] = MergeConfig[attrname]; }}</script>',
				true
			);
			if ($config_js['enable_editor']) {
				$this->modx->regClientStartupScript(
					'<script type="text/javascript">TicketsConfig.editor={ticket: ' . $this->modx->getOption('tickets.editor_config.ticket') . ',comment: ' . $this->modx->getOption('tickets.editor_config.comment') . '};</script>',
					true
				);
				$this->modx->regClientScript($this->config['jsUrl'] . 'web/editor/jquery.markitup.js');
				$this->modx->regClientCSS($this->config['jsUrl'] . 'web/editor/editor.css');
			}
			$this->initialized[$ctx] = true;
		}

		if (!defined('MODX_API_MODE') || !MODX_API_MODE) {
			$config = $this->makePlaceholders($this->config);

			$css = !empty($this->config['frontend_css'])
				? $this->config['frontend_css']
				: $this->modx->getOption('tickets.frontend_css');
			if (!empty($css) && preg_match('/\.css/i', $css)) {
				$this->modx->regClientCSS(str_replace($config['pl'], $config['vl'], $css));
			}

			$js = !empty($this->config['frontend_js'])
				? $this->config['frontend_js']
				: $this->modx->getOption('tickets.frontend_js');
			if (!empty($js) && preg_match('/\.js/i', $js)) {
				$this->modx->regClientScript(str_replace($config['pl'], $config['vl'], $js));
			}
		}

		return true;
	}

	/**
	 * Shorthand for the call of processor.
	 *
	 * @param string $action Path to processor
	 * @param array  $data   Data to be transmitted to the processor
	 *
	 * @return mixed The result of the processor
	 */
	public function runProcessor($action = '', $data = [])
	{
		if (empty($action)) {
			return false;
		}
		$this->modx->error->reset();
		$processorsPath = !empty($this->config['processorsPath'])
			? $this->config['processorsPath']
			: MODX_CORE_PATH . 'components/tickets/src/Processors/';

		return $this->modx->runProcessor($action, $data, ['processors_path' => $processorsPath]);
	}

	/**
	 * Returns sanitized preview of Ticket.
	 *
	 * @param array $data section, pagetitle, text, etc
	 *
	 * @return array
	 */
	public function previewTicket($data = [])
	{
		$message = '';
		foreach ($data as $k => $v) {
			if ('content' == $k) {
				if (!$data[$k] = $this->sanitizeText($v, true)) {
					return $this->error($this->modx->lexicon('err_no_jevix'));
				}
			} else {
				$data[$k] = $this->sanitizeString($v);
			}
		}

		$preview = $this->getChunk($this->config['tplPreview'], $data);
		$preview = $this->pdoTools->fastProcess($preview);

		return $this->success($message, ['preview' => $preview]);
	}

	/**
	 * Delete/unDelete ticket through processor and redirect to link.
	 *
	 * @param array $data    id, redirect
	 * @param array $restore bool
	 *
	 * @return array
	 */
	public function deleteTicket($data, $restore = false)
	{
		$restore = (bool) $restore;
		$id      = (int) $data['id'];
		if (empty($data['id'])) {
			return $this->error($this->modx->lexicon('ticket_err_id', ['id' => $id]));
		}
		$fields              = [];
		$fields['class_key'] = Ticket::class;
		$fields['id']        = $id;
		$processorname       = $restore ? TicketUndeleteProcessor::class : TicketDeleteProcessor::class;
		$response            = $this->runProcessor($processorname, $fields);

		/** @var ProcessorResponse $response */
		if ($response->isError()) {
			$this->modx->log(
				modX::LOG_LEVEL_INFO,
				'[Tickets] Unable to delete Ticket: ' . $response->getMessage()
			);

			return $this->error($response->getMessage(), $response->getFieldErrors());
		} else {
			$message  = $this->modx->lexicon($restore ? 'ticket_undeleted_text' : 'ticket_deleted_text');
			$is_redir = $restore ? 'redirectUnDeleted' : 'redirectDeleted';
			if (!empty($this->config[$is_redir])) {
				$url = $this->modx->makeUrl((int) $this->config[$is_redir], '', '', 'full');
			} else {
				$url = $_SERVER['HTTP_REFERER'];
				if (!preg_match('/\b' . $id . '\b/', $url)) {
					$url .= false !== strpos($url, '?')
						? '&tid=' . $id
						: '?tid=' . $id;
				}
			}
			if (empty($url)) {
				$url = $this->modx->getOption('site_url');
			}
			$results['redirect'] = $url;
		}

		return $this->success($message, $results);
	}

	/**
	 * Save ticket through processor and redirect to it.
	 *
	 * @param array $data section, pagetitle, text, etc
	 *
	 * @return array
	 */
	public function saveTicket($data = [])
	{
		$requiredFields = array_map('trim', explode(',', $this->config['requiredFields']));
		$requiredFields = array_unique(array_merge($requiredFields, ['parent', 'pagetitle', 'content']));
		$allowedFields  = array_map('trim', explode(',', $this->config['allowedFields']));
		$allowedFields  = array_unique(array_merge($allowedFields, $requiredFields));
		$bypassFields   = array_map('trim', explode(',', $this->config['bypassFields']));

		$validate  = $this->config['validate'];
		$modelPath = $this->modx->getOption('formit.core_path', null, $this->modx->getOption('core_path') . 'components/formit/') . 'model/formit/';
		if (!empty($validate) && file_exists($modelPath . 'formit.class.php')) {
			$fi = $this->modx->services->get(
				'formit',
				\Sterc\FormIt::class,
				$modelPath,
				$this->config
			);

			if ($fi instanceof \Sterc\FormIt) {
				$fi->initialize($this->modx->context->get('key'));
				$fi->loadRequest();

				$fields = $fi->request->prepare();
				$fi->request->handle($fields);

				$errors = $fi->request->validator->getRawErrors();
				if (!empty($errors)) {
					$data = [];
					foreach ($errors as $field => $message) {
						$data[$field] = ['field' => $field, 'message' => $message];
					}

					return $this->error('', $data);
				}
			}
		}

		$fields = [];
		foreach ($allowedFields as $field) {
			if (in_array($field, $allowedFields, true) && array_key_exists($field, $data)) {
				$value = $data[$field];
				if ('content' !== $field && !in_array($field, $bypassFields, true)) {
					$value = $this->sanitizeString($value);
				}
				$fields[$field] = $value;
			}
		}

		switch ($data['action']) {
			case 'ticket/save':
				$fields['published'] = null;
				break;
			case 'ticket/draft':
				$fields['published'] = false;
				break;
			default:
				$fields['published'] = true;
		}

		$fields['requiredFields'] = $requiredFields;
		$fields['class_key']      = Ticket::class;
		if (!empty($this->config['sections']) && is_array($this->config['sections'])) {
			$fields['sections'] = $this->config['sections'];
		}
		if (!empty($data['tid'])) {
			$fields['id'] = (int) $data['tid'];
			if ($ticket = $this->modx->getObject(Ticket::class, ['class_key' => Ticket::class, 'id' => $fields['id']])) {
				$fields['context_key'] = $ticket->get('context_key');
				$fields['alias']       = $ticket->get('alias');
				$response              = $this->modx->runProcessor(ResourceUpdateProcessor::class, $fields);
			} else {
				return $this->error($this->modx->lexicon('ticket_err_id', ['id' => $fields['id']]));
			}
		} else {
			$response = $this->modx->runProcessor(ResourceCreateProcessor::class, $fields);
		}

		/** @var ProcessorResponse $response */
		if ($response->isError()) {
			$this->modx->log(
				modX::LOG_LEVEL_INFO,
				'[Tickets] Unable to save Ticket: ' . $response->getMessage() . print_r(
					$response->getFieldErrors(),
					1
				)
			);

			return $this->error($response->getMessage(), $response->getFieldErrors());
		} elseif ($ticket = $this->modx->getObject(Ticket::class, $response->response['object']['id'])) {
			$ticket = $ticket->toArray();
			$this->sendTicketMails($ticket);
		}

		$id      = $response->response['object']['id'];
		$message = '';
		$results = [
			'id'      => $id,
			'content' => !empty($ticket['content'])
				? html_entity_decode($ticket['content'])
				: '',
		];
		switch ($data['action']) {
			case 'ticket/save':
				$message = $this->modx->lexicon('ticket_saved');
				break;
			case 'ticket/draft':
				if (!empty($this->config['redirectUnpublished'])) {
					$url = $this->modx->makeUrl((int) $this->config['redirectUnpublished'], '', '', 'full');
				} else {
					$url = $_SERVER['HTTP_REFERER'];
					if (!preg_match('/\b' . $id . '\b/', $url)) {
						$url .= false !== strpos($url, '?')
							? '&tid=' . $id
							: '?tid=' . $id;
					}
				}
				if (empty($url)) {
					$url = $this->modx->getOption('site_url');
				}
				$results['redirect'] = $url;
				break;
			default:
				$url = $this->modx->makeUrl($id, '', '', 'full');
				if (empty($url)) {
					$url = $this->modx->getOption('site_url');
				}
				$results['redirect'] = $url;
		}

		if ($this->modx->getOption('ms2gallery_sync_tickets')) {
			/** @var ms2Gallery $ms2Gallery */
			$ms2Gallery = $this->modx->services->get(
				'ms2gallery',
				'ms2Gallery',
				MODX_CORE_PATH . 'components/ms2gallery/model/ms2gallery/'
			);
			if ($ms2Gallery && method_exists($ms2Gallery, 'syncFiles')) {
				$ms2Gallery->syncFiles('tickets', $id, true);
			}
		}

		return $this->success($message, $results);
	}

	/**
	 * Vote for ticket.
	 *
	 * @param int $value
	 *
	 * @return array|string
	 */
	public function voteTicket($id, $value = 1)
	{
		$data = ['id' => $id, 'value' => $value];
		/** @var ProcessorResponse $response */
		if (!empty($id)) {
			$response = $this->runProcessor(TicketVoteProcessor::class, $data);
			if ($response->isError()) {
				return $this->error($response->getMessage());
			} else {
				$data          = $response->getObject();
				$rating        = abs($data['rating_plus']) + abs($data['rating_minus']);
				$data['title'] = $this->modx->lexicon('ticket_rating_total')
					. " {$rating}: ↑{$data['rating_plus']} "
					. $this->modx->lexicon('ticket_rating_and')
					. " ↓{$data['rating_minus']}";
				if ($data['rating'] > 0) {
					$data['rating'] = '+' . $data['rating'];
					$data['status'] = 1;
				} elseif ($data['rating'] < 0) {
					$data['status'] = -1;
				} else {
					$data['status'] = 0;
				}

				return $this->success('', $data);
			}
		}

		return $this->error('tickets_err_unknown');
	}

	/**
	 * Star for ticket.
	 *
	 * @return array|string
	 */
	public function starTicket($id)
	{
		$data = ['id' => $id];
		/** @var ProcessorResponse $response */
		if (!empty($id)) {
			$response = $this->runProcessor('web/ticket/star', $data);
			if ($response->isError()) {
				return $this->error($response->getMessage());
			} else {
				$data = $response->getObject();

				return $this->success('', $data);
			}
		}

		return $this->error('tickets_err_unknown');
	}

	/**
	 * Returns sanitized preview of Comment.
	 *
	 * @param array $data section, pagetitle, comment, etc
	 *
	 * @return array
	 */
	public function previewComment($data = [])
	{
		unset($data['action']);

		// Additional properties
		$properties = [];
		$meta       = $this->modx->getFieldMeta(TicketComment::class);
		foreach ($data as $k => $v) {
			if (!isset($meta[$k])) {
				$properties[$k] = $this->modx->stripTags($v);
			}
		}

		$preview = $this->sanitizeText($data['text'], true);
		$preview = preg_replace('/\[\[.*?\]\]/', '', $preview);

		return $this->success('', ['preview' => $preview]);
	}

	/**
	 * Create or update Comment.
	 *
	 * @param array $data section, pagetitle, comment, etc
	 *
	 * @return array
	 */
	public function saveComment($data = [])
	{
		$validate  = $this->config['validate'];
		$modelPath = $this->modx->getOption('formit.core_path', null, $this->modx->getOption('core_path') . 'components/formit/') . 'model/formit/';
		if (!empty($validate) && file_exists($modelPath . 'formit.class.php')) {
			$fi = $this->modx->services->get(
				'formit',
				\Sterc\FormIt::class,
				$modelPath,
				$this->config
			);

			if ($fi instanceof \Sterc\FormIt) {
				$fi->initialize($this->modx->context->get('key'));
				$fi->loadRequest();

				$fields = $fi->request->prepare();
				$fi->request->handle($fields);

				$errors = $fi->request->validator->getRawErrors();
				if (!empty($errors)) {
					$data = [];
					foreach ($errors as $field => $message) {
						$data[$field] = ['field' => $field, 'message' => $message];
					}

					return $this->error('', $data);
				}
			}
		}
		unset($data['action']);
		$data['raw']            = trim($data['text']);
		$data['text']           = $this->sanitizeText($data['text'], true);
		$data['allowGuest']     = !empty($this->config['allowGuest']);
		$data['allowGuestEdit'] = !empty($this->config['allowGuestEdit']);
		$data['requiredFields'] = $this->config['requiredFields'];
		$data['published']      = (!$this->authenticated && empty($this->config['autoPublishGuest'])) || ($this->authenticated && empty($this->config['autoPublish']))
			? false
			: true;
		if ($this->authenticated) {
			if (empty($data['name'])) {
				$data['name'] = $this->modx->user->Profile->get('fullname');
			}
			$data['email'] = $this->modx->user->Profile->get('email');
		} else {
			if (!empty($this->config['enableCaptcha'])) {
				if ($data['captcha'] != $_SESSION['TicketComments']['captcha']) {
					$captcha = $this->modx->lexicon('ticket_comment_captcha', $this->getCaptcha());

					return $this->error(
						$this->modx->lexicon('ticket_comment_err_captcha'),
						['captcha' => $captcha]
					);
				}
			}
			$data['name'] = !empty($data['name'])
				? $data['name']
				: '';
			$data['email'] = !empty($data['email'])
				? $data['email']
				: '';
		}
		unset($data['rating'], $data['rating_plus'], $data['rating_minus']);

		if (!empty($data['id'])) {
			$response = $this->runProcessor('web/comment/update', $data);
		} else {
			$response = $this->runProcessor('web/comment/create', $data);
		}
		/** @var ProcessorResponse $response */
		if ($response->isError()) {
			$this->modx->log(
				modX::LOG_LEVEL_INFO,
				'[Tickets] Unable to save Comment: ' . $response->getMessage() . print_r(
					$response->getFieldErrors(),
					1
				)
			);

			return $this->error($response->getMessage(), $response->getFieldErrors());
		} else {
			$comment               = $response->getObject();
			$comment['mode']       = 'save';
			$comment['new_parent'] = $data['parent'];
			$comment['resource']   = $this->config['resource'];
			$comment['vote']       = $comment['star'] = '';

			/** @var modUser $user */
			if ($user = $this->modx->getObject(modUser::class, $comment['createdby'])) {
				/** @var modUserProfile $profile */
				$profile = $user->getOne('Profile');
				$comment = array_merge($profile->toArray(), $user->toArray(), $comment);
			}

			if (empty($data['id'])) {
				$this->sendCommentMails($comment);
			}

			$data            = [];
			$data['captcha'] = empty($comment['createdby']) && !empty($this->config['enableCaptcha'])
				? $this->modx->lexicon('ticket_comment_captcha', $this->getCaptcha())
				: '';
			if ($comment['published']) {
				$this->modx->cacheManager->delete('tickets/latest.comments');
				$this->modx->cacheManager->delete('tickets/latest.tickets');
				$comment         = $this->templateNode($comment, $this->config['tplCommentAuth']);
				$data['comment'] = preg_replace('/\[\[.*?\]\]/', '', $comment);

				return $this->success('', $data);
			} else {
				return $this->success($this->modx->lexicon('ticket_unpublished_comment'), $data);
			}
		}
	}

	/**
	 * Vote for comment.
	 *
	 * @param int $value
	 *
	 * @return array|string
	 */
	public function voteComment($id, $value = 1)
	{
		$data = ['id' => $id, 'value' => $value];

		/** @var ProcessorResponse $response */
		if (!empty($id) && !empty($value)) {
			$response = $this->runProcessor('web/comment/vote', $data);
			if ($response->isError()) {
				return $this->error($response->getMessage());
			} else {
				$data          = $response->getObject();
				$rating        = abs($data['rating_plus']) + abs($data['rating_minus']);
				$data['title'] = $this->modx->lexicon('ticket_rating_total')
					. " {$rating}: ↑{$data['rating_plus']} "
					. $this->modx->lexicon('ticket_rating_and')
					. " ↓{$data['rating_minus']}";
				if ($data['rating'] > 0) {
					$data['rating'] = '+' . $data['rating'];
					$data['status'] = 1;
				} elseif ($data['rating'] < 0) {
					$data['status'] = -1;
				} else {
					$data['status'] = 0;
				}

				return $this->success('', $data);
			}
		}

		return $this->error('tickets_err_unknown');
	}

	/**
	 * Star for comment.
	 *
	 * @return array|string
	 */
	public function starComment($id)
	{
		$data = ['id' => $id];
		/** @var ProcessorResponse $response */
		if (!empty($id)) {
			$response = $this->runProcessor('web/comment/star', $data);
			if ($response->isError()) {
				return $this->error($response->getMessage());
			} else {
				$data = $response->getObject();

				return $this->success('', $data);
			}
		}

		return $this->error('tickets_err_unknown');
	}

	/**
	 * Returns Comment for edit by its author.
	 *
	 * @param int $id Id of an comment
	 *
	 * @return array
	 */
	public function getComment($id)
	{
		$response = $this->runProcessor('web/comment/get', ['id' => $id]);
		if ($response->isError()) {
			return $this->error($response->getMessage());
		}

		$comment    = $response->getObject();
		$time       = time() - strtotime($comment['createdon']);
		$time_limit = $this->config['commentEditTime'];

		if ($this->authenticated && $this->modx->user->id != $comment['createdby']) {
			return $this->error($this->modx->lexicon('ticket_comment_err_wrong_user'));
		} elseif (!$this->authenticated) {
			if (!$this->config['allowGuest'] || !$this->config['allowGuestEdit']) {
				return $this->error($this->modx->lexicon('ticket_comment_err_guest_edit'));
			} elseif (!isset($_SESSION['TicketComments']['ids'][$id])) {
				return $this->error($this->modx->lexicon('ticket_comment_err_wrong_user'));
			}
		} elseif ($this->modx->getCount('TicketComment', ['parent' => $comment['id']])) {
			return $this->error($this->modx->lexicon('ticket_comment_err_has_replies'));
		} elseif ($time >= $time_limit) {
			return $this->error($this->modx->lexicon('ticket_comment_err_no_time'));
		}

		$data = [
			'raw'   => $comment['raw'],
			'time'  => $time_limit - $time,
			'files' => $this->getFileComment($id),
		];
		if (empty($comment['createdby'])) {
			$data['name']  = $comment['name'];
			$data['email'] = $comment['email'];
		}

		return $this->success('', $data);
	}

	/**
	 * Returns TicketFile for TicketComment current author.
	 *
	 * @param int $tid
	 *
	 * @return string
	 */
	public function getFileComment($tid = 0)
	{
		if (empty($this->config['allowFiles'])) {
			return '';
		}
		$q = $this->modx->newQuery('TicketFile');
		$q->where(['class' => 'TicketComment']);
		if (!empty($tid)) {
			$q->andCondition(['parent' => $tid, 'createdby' => $this->modx->user->id], null, 1);
		} else {
			$q->andCondition(['parent' => 0, 'createdby' => $this->modx->user->id], null, 1);
		}
		$q->sortby('rank', 'ASC');
		$q->sortby('createdon', 'ASC');
		$collection = $this->modx->getIterator('TicketFile', $q);
		$files      = '';
		/** @var TicketFile $item */
		foreach ($collection as $item) {
			if ($item->get('deleted') && !$item->get('parent')) {
				$item->remove();
			} else {
				$item         = $item->toArray();
				$item['size'] = round($item['size'] / 1024, 2);
				$item['new']  = empty($item['parent']);
				$tpl          = 'image' == $item['type']
					? $this->config['tplImage']
					: $this->config['tplFile'];
				$files .= $this->getChunk($tpl, $item);
			}
		}

		$chunk = $this->getChunk($this->config['tplFiles'], [
			'files' => $files,
		]);

		return $chunk;
	}

	/**
	 * Return unseen comments of thread for user.
	 *
	 * @param string $name
	 * @param bool   $log
	 *
	 * @return array
	 */
	public function getNewComments($name, $log = true)
	{
		if (!$this->authenticated) {
			return $this->error($this->modx->lexicon('access_denied'));
		} elseif ($thread = $this->modx->getObject(TicketThread::class, ['name' => $name])) {
			if (
				$this->authenticated && $view = $this->modx->getObject(
					TicketView::class,
					['uid' => $this->modx->user->id, 'parent' => $thread->get('resource')]
				)
			) {
				$date = $view->get('timestamp');
				$q    = $this->modx->newQuery(TicketComment::class);
				$q->leftJoin(modUser::class, 'User', '`User`.`id` = `TicketComment`.`createdby`');
				$q->leftJoin(modUserProfile::class, 'Profile', '`Profile`.`internalKey` = `TicketComment`.`createdby`');
				$q->where([
					'`TicketComment`.`published`'    => 1,
					'`TicketComment`.`thread`'       => $thread->id,
					'`TicketComment`.`createdby`:!=' => $this->modx->user->id,
				]);
				$q->andCondition([
					'`TicketComment`.`createdon`:>'   => $date,
					'OR:`TicketComment`.`editedon`:>' => $date,
				]);

				$q->sortby('`TicketComment`.`id`', 'ASC');
				$q->select($this->modx->getSelectColumns('TicketComment', 'TicketComment'));
				$q->select($this->modx->getSelectColumns(modUser::class, 'User', '', ['username']));
				$q->select($this->modx->getSelectColumns(modUserProfile::class, 'Profile', '', ['id'], true));

				$comments = [];
				if ($q->prepare() && $q->stmt->execute()) {
					while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
						$row['resource']   = $thread->resource;
						$row['new_parent'] = $row['parent'];

						$tmp                  = $this->templateNode($row);
						$comments[$row['id']] = $tmp;
					}

					if (true === $log) {
						$this->logView($thread->resource);
					}

					return $this->success('', [
						'comments' => $comments,
					]);
				}
			}
		}

		return $this->error('');
	}

	public function sanitizeText($text = null, $replaceTags = true)
	{
		if (empty($text)) {
			return ' ';
		}

		// Loading parser if needed - it is for mgr context
		if (!is_object($this->modx->parser)) {
			$this->modx->getParser();
		}

		$text            = html_entity_decode($text, ENT_COMPAT, 'UTF-8');
		$params['input'] = str_replace(
			['[', ']', '{', '}'],
			['*(*(*(*(*(*', '*)*)*)*)*)*', '~(~(~(~(~(~', '~)~)~)~)~)~'],
			$text
		);

		$builder = (new HtmlSanitizerConfig())
			->allowSafeElements()                   // безопасный базовый набор
			// ->allowElement('['a', 'p', 'ul', 'ol', 'li', 'strong', 'em', 'code', 'blockquote', 'br']')
			->allowElement('a')
			->allowElement('p')
			->allowElement('ul')
			->allowElement('ol')
			->allowElement('li')
			->allowElement('strong')
			->allowElement('em')
			->allowElement('code')
			->allowElement('blockquote')
			->allowElement('br')
			->allowAttribute('href', 'a')
			->allowAttribute('title', 'a')
			->forceAttribute('a', 'rel', 'nofollow noopener ugc')
			->forceAttribute('a', 'target', '_blank')
			->allowLinkSchemes(['http', 'https', 'mailto'])
			->allowRelativeLinks();

		$sanitizer = new HtmlSanitizer($builder);
		$filtered  = $sanitizer->sanitize($text);

		if ($replaceTags) {
			$filtered = str_replace(
				['*(*(*(*(*(*', '*)*)*)*)*)*', '`', '~(~(~(~(~(~', '~)~)~)~)~)~'],
				['&#91;', '&#93;', '&#96;', '&#123;', '&#125;'],
				$filtered
			);
		} else {
			$filtered = str_replace(
				['*(*(*(*(*(*', '*)*)*)*)*)*', '~(~(~(~(~(~', '~)~)~)~)~)~'],
				['[', ']', '{', '}'],
				$filtered
			);
		}

		return $filtered;
	}

	/**
	 * Sanitize MODX tags.
	 *
	 * @param string $string Any string with MODX tags
	 *
	 * @return string String with html entities
	 */
	public function sanitizeString($string = '')
	{
		if (is_array($string)) {
			foreach ($string as $key => $value) {
				$string[$key] = $this->sanitizeString($value);
			}

			return $string;
		}

		$string = htmlentities(trim($string), ENT_QUOTES, 'UTF-8');
		$string = preg_replace('/^@.*\b/', '', $string);
		$string = str_replace(
			['[', ']', '`', '{', '}'],
			['&#91;', '&#93;', '&#96;', '&#123;', '&#125;'],
			$string
		);

		return $string;
	}

	/**
	 * Recursive template of the comment node.
	 *
	 * @param array $node
	 * @param null  $tpl
	 *
	 * @return string
	 */
	public function templateNode($node = [], $tpl = null)
	{
		$children = null;
		if (!empty($node['children'])) {
			foreach ($node['children'] as $v) {
				$children .= $this->templateNode($v, $tpl);
			}
		}
		$node['has_parent'] = !empty($node['parent']);
		// Handle rating
		if (isset($node['ratings']['days_comment_vote'])) {
			if ('' !== $node['ratings']['days_comment_vote']) {
				$max = strtotime($node['createdon']) + ((float) $node['ratings']['days_comment_vote'] * 86400);
				if (time() > $max) {
					$node['cant_vote'] = 1;
				}
			}
		}
		if (!isset($node['cant_vote'])) {
			if (!$this->authenticated || $this->modx->user->id == $node['createdby']) {
				$node['cant_vote'] = 1;
			} elseif (array_key_exists('vote', $node)) {
				if (empty($node['vote'])) {
					$node['can_vote'] = 1;
				} elseif ($node['vote'] > 0) {
					$node['voted_plus'] = 1;
					$node['cant_vote']  = 1;
				} elseif ($node['vote'] < 0) {
					$node['voted_minus'] = 1;
					$node['cant_vote']   = 1;
				}
			}
		}
		if ($node['rating'] > 0) {
			$node['rating']          = '+' . $node['rating'];
			$node['rating_positive'] = 1;
			$node['bad']             = '';
		} elseif ($node['rating'] < 0) {
			$node['rating_negative'] = 1;
			$node['bad']             = $node['rating'] >= -5
				? ' bad bad' . abs($node['rating'])
				: ' bad bad5';
		} else {
			$node['bad'] = '';
		}
		$node['rating_total'] = abs($node['rating_plus']) + abs($node['rating_minus']);

		// Handle stars
		if ($this->authenticated && array_key_exists('star', $node)) {
			$node['can_star'] = 1;
			$node['stared']   = !empty($node['star']);
			$node['unstared'] = empty($node['star']);
		}

		// Check comment novelty
		if (isset($node['resource']) && 0 === $this->last_view) {
			if (
				$this->authenticated && $view = $this->modx->getObject(
					TicketView::class,
					['parent' => $node['resource'], 'uid' => $this->modx->user->id]
				)
			) {
				$this->last_view = strtotime($view->get('timestamp'));
			} else {
				$this->last_view = -1;
			}
		}

		// Processing comment and selecting needed template
		$node = $this->prepareComment($node);
		if (empty($tpl)) {
			$tpl = $this->authenticated || !empty($this->config['allowGuest'])
				? $this->config['tplCommentAuth']
				: $this->config['tplCommentGuest'];
		}
		if ($node['deleted']) {
			$tpl = $this->config['tplCommentDeleted'];
		}
		// Special processing for guests
		if (!empty($node['user_email'])) {
			$node['email'] = $node['user_email'];
		}
		unset($node['user_email']);
		if (empty($node['fullname']) && !empty($node['name'])) {
			$node['fullname'] = $node['name'];
		}
		$node['guest'] = empty($node['createdby']);
		// --

		if (!empty($children) || !empty($node['has_children'])) {
			$node['children']          = $children;
			$node['comment_edit_link'] = false;
		} elseif (time() - strtotime($node['createdon']) <= $this->config['commentEditTime']) {
			if ($this->modx->user->id && $node['createdby'] == $this->modx->user->id) {
				$node['comment_edit_link'] = true;
			} elseif ($this->config['allowGuest'] && $this->config['allowGuestEdit']) {
				if (isset($_SESSION['TicketComments']['ids'][$node['id']])) {
					$node['comment_edit_link'] = true;
				}
			}
			$node['children'] = '';
		} else {
			$node['children'] = '';
		}
		$node['comment_was_edited'] = (bool) $node['editedon'];
		$node['comment_new']        = $this->authenticated && $node['createdby'] != $this->modx->user->id && $this->last_view > 0 && strtotime($node['createdon']) > $this->last_view;

		return $this->getChunk($tpl, $node, $this->config['fastMode']);
	}

	/**
	 * Render of the comment.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function prepareComment($data = [])
	{
		if (!empty($this->prepareCommentCustom)) {
			return eval($this->prepareCommentCustom);
		} else {
			$data['gravatar'] = $this->config['gravatarUrl'] . md5(strtolower($data['email'])) . '?s=' . $this->config['gravatarSize'] . '&d=' . $this->config['gravatarIcon'];
			$data['avatar']   = !empty($data['photo'])
				? $data['photo']
				: $data['gravatar'];
			if (!empty($data['resource'])) {
				$data['url'] = $this->modx->makeUrl($data['resource'], '', '', 'full');
			}
			$data['date_ago'] = $this->dateFormat($data['createdon']);

			return $data;
		}
	}

	/**
	 * Method for transform array to placeholders.
	 *
	 * @var array  With keys and values
	 * @var string Prefix for array keys
	 *
	 * @return array $array Two nested arrays with placeholders and values
	 */
	public function makePlaceholders(array $array = [], $prefix = '')
	{
		if (!$this->pdoTools) {
			$this->loadPdoTools();
		}

		return $this->pdoTools->makePlaceholders($array, $prefix);
	}

	/**
	 * Email notifications about new comment.
	 *
	 * @param array $ticket
	 * @param bool  $force
	 */
	public function sendTicketMails($ticket = [], $force = false)
	{
		// We need only the first publication of ticket
		if (!$force) {
			if (empty($ticket['published']) || $ticket['createdon'] != $ticket['publishedon']) {
				return;
			} elseif (0 != $ticket['editedon'] && $ticket['editedon'] != $ticket['createdon']) {
				return;
			}
		}

		/** @var TicketsSection $section */
		if ($section = $this->modx->getObject(TicketsSection::class, $ticket['parent'], false)) {
			$properties  = $section->get('properties');
			$subscribers = !empty($properties['subscribers'])
				? $properties['subscribers']
				: [];
			$ticket = array_merge($ticket, $section->toArray('section.'));
		}
		/** @var modUser $user */
		if ($user = $this->modx->getObject(modUser::class, $ticket['createdby'])) {
			if ($profile = $user->getOne('Profile')) {
				$ticket = array_merge($ticket, array_merge($profile->toArray('user.'), $user->toArray('user.')));
			}
		}

		// Send notifications to admin
		$sent = [];
		if ($this->modx->getOption('tickets.mail_bcc_level') >= 1) {
			if ($bcc = $this->modx->getOption('tickets.mail_bcc')) {
				$bcc = array_map('trim', explode(',', $bcc));
				if (!empty($bcc)) {
					foreach ($bcc as $uid) {
						if ($uid == $ticket['createdby']) {
							continue;
						}
						$this->addQueue(
							$uid,
							$this->modx->lexicon('ticket_email_bcc', $ticket),
							$this->getChunk($this->config['tplTicketEmailBcc'], $ticket, false)
						);
						$sent[] = $uid;
					}
				}
			}
		}

		// Send email to subscribers current author
		if ($author = $this->modx->getObject(TicketAuthor::class, ['id' => $ticket['createdby']])) {
			$properties = $author->get('properties');
			if (!empty($properties['subscribers'])) {
				foreach ($properties['subscribers'] as $uid) {
					if (in_array($uid, $sent, true) || $ticket['createdby'] == $uid) {
						continue;
					} else {
						$this->addQueue(
							$uid,
							$this->modx->lexicon('tickets_author_email_subscription', $ticket),
							$this->getChunk($this->config['tplAuthorEmailSubscription'] ? $this->config['tplAuthorEmailSubscription'] : 'tpl.Tickets.author.email.subscription', $ticket, false)
						);
						$sent[] = $uid;
					}
				}
			}
		}

		// Then we send emails to subscribers
		if (!empty($subscribers)) {
			foreach ($subscribers as $uid) {
				if (in_array($uid, $sent, true) || $ticket['createdby'] == $uid) {
					continue;
				} else {
					$this->addQueue(
						$uid,
						$this->modx->lexicon('tickets_section_email_subscription', $ticket),
						$this->getChunk($this->config['tplTicketEmailSubscription'], $ticket, false)
					);
				}
			}
		}
	}

	/**
	 * Email notifications about new comment.
	 *
	 * @param array $comment
	 */
	public function sendCommentMails($comment = [])
	{
		$owner_uid   = $reply_uid = $reply_email = null;
		$subscribers = [];
		$q           = $this->modx->newQuery(TicketThread::class);
		$q->leftJoin(modResource::class, 'modResource', 'TicketThread.resource = modResource.id');
		$q->select('modResource.createdby as uid, modResource.id as resource, modResource.pagetitle, TicketThread.subscribers');
		$q->where(['TicketThread.id' => $comment['thread']]);
		if ($q->prepare() && $q->stmt->execute()) {
			$res = $q->stmt->fetch(PDO::FETCH_ASSOC);
			if (!empty($res)) {
				$comment = array_merge($comment, [
					'resource'  => $res['resource'],
					'pagetitle' => $res['pagetitle'],
					'author'    => $res['uid'],
				]);
				$owner_uid   = $res['uid'];
				$subscribers = json_decode($res['subscribers'], true);
			}
		}

		$comment = $this->prepareComment($comment);
		$sent    = [];

		// It is a reply for a comment
		if ($comment['parent']) {
			$q = $this->modx->newQuery(TicketComment::class);
			$q->select('TicketComment.createdby as uid, TicketComment.text, TicketComment.email');
			$q->where(['TicketComment.id' => $comment['parent']]);
			if ($q->prepare() && $q->stmt->execute()) {
				if ($res = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
					$reply_uid              = $res['uid'];
					$reply_email            = $res['email'];
					$comment['parent_text'] = $res['text'];
				}
			}
		}

		$published              = !empty($comment['published']) && !array_key_exists('was_published', $comment['properties']);
		$comment['manager_url'] = trim(
			$this->modx->getOption('site_url'),
			'/'
		) . MODX_MANAGER_URL . '?a=home&namespace=tickets';

		if ($published) {
			// We always send replies for comments
			if (($reply_uid && $reply_uid != $comment['createdby']) || ($reply_email && $reply_email != $comment['email'])) {
				$this->addQueue(
					$reply_uid,
					$this->modx->lexicon('ticket_comment_email_reply', $comment),
					$this->getChunk($this->config['tplCommentEmailReply'], $comment, false),
					$reply_email
				);
				$sent[] = $reply_uid;
			}
		}

		// Then we make blind copy to administrators
		if ($this->modx->getOption('tickets.mail_bcc_level') >= 2 || !$published) {
			if ($bcc = $this->modx->getOption('tickets.mail_bcc')) {
				$bcc = array_map('trim', explode(',', $bcc));
				foreach ($bcc as $uid) {
					if ($published && (in_array($uid, $sent, true) || $uid == $owner_uid || $uid == $comment['createdby'])) {
						continue;
					}
					$this->addQueue(
						$uid,
						!$published
							? $this->modx->lexicon('ticket_comment_email_unpublished_bcc', $comment)
							: $this->modx->lexicon('ticket_comment_email_bcc', $comment),
						!$published
							? $this->getChunk($this->config['tplCommentEmailUnpublished'], $comment, false)
							: $this->getChunk($this->config['tplCommentEmailBcc'], $comment, false)
					);
					$sent[] = $uid;
				}
			}
		}

		if ($published) {
			if (!empty($subscribers)) {
				// And send emails to subscribers
				foreach ($subscribers as $uid) {
					if (in_array($uid, $sent, true) || $uid == $comment['createdby']) {
						continue;
					} elseif ($uid == $owner_uid) {
						$this->addQueue(
							$uid,
							$this->modx->lexicon('ticket_comment_email_owner', $comment),
							$this->getChunk($this->config['tplCommentEmailOwner'], $comment, false)
						);
					} else {
						$this->addQueue(
							$uid,
							$this->modx->lexicon('ticket_comment_email_subscription', $comment),
							$this->getChunk($this->config['tplCommentEmailSubscription'], $comment, false)
						);
					}
				}
			}
		}
	}

	/**
	 * Adds emails to queue.
	 *
	 * @return bool|string
	 */
	public function addQueue($uid, $subject, $body, $email = '')
	{
		$uid   = (int) $uid;
		$email = trim($email);

		if (empty($uid) && (empty($this->config['allowGuestEmails']) || empty($email))) {
			return false;
		}

		/** @var TicketQueue $queue */
		$queue = $this->modx->newObject(
			TicketQueue::class,
			[
				'uid'     => $uid,
				'subject' => $subject,
				'body'    => $body,
				'email'   => $email,
			]
		);

		return $this->modx->getOption('tickets.mail_queue', null, false, true)
			? $queue->save()
			: $queue->Send();
	}

	/** @deprecated
	 *
	 * @return array
	 */
	public function subscribe($name)
	{
		return $this->subscribeThread($name);
	}

	/**
	 * This method subscribe or unsubscribe users for notifications about new comments in thread.
	 *
	 * @param string $name Name of tickets thread for subscribe or unsubscribe
	 *
	 * @return array
	 */
	public function subscribeThread($name)
	{
		if (!$this->authenticated) {
			return $this->error('ticket_err_access_denied');
		}
		/** @var TicketThread $thread */
		if ($thread = $this->modx->getObject(TicketThread::class, ['name' => $name])) {
			$message = $thread->Subscribe() ? 'ticket_thread_subscribed' : 'ticket_thread_unsubscribed';

			return $this->success($this->modx->lexicon($message));
		} else {
			return $this->error($this->modx->lexicon('ticket_err_wrong_thread'));
		}
	}

	/**
	 * This method subscribe or unsubscribe users for notifications about new tickets in section.
	 *
	 * @return array
	 */
	public function subscribeSection($id)
	{
		if (!$this->authenticated) {
			return $this->error('ticket_err_access_denied');
		}
		/** @var TicketsSection $section */
		if ($section = $this->modx->getObject(TicketsSection::class, ['id' => $id, 'class_key' => TicketsSection::class])) {
			$message = $section->Subscribe() ? 'tickets_section_subscribed' : 'tickets_section_unsubscribed';

			return $this->success($this->modx->lexicon($message));
		} else {
			return $this->error($this->modx->lexicon('ticket_err_wrong_section'));
		}
	}

	/**
	 * This method subscribe or unsubscribe users for notifications about new tickets specified author.
	 *
	 * @return array
	 */
	public function subscribeAuthor($id)
	{
		if (!$this->authenticated) {
			return $this->error('ticket_err_access_denied');
		}
		/** @var TicketAuthor $profile */
		if ($profile = $this->modx->getObject(TicketAuthor::class, ['id' => $id])) {
			$message = $profile->Subscribe() ? 'tickets_author_subscribed' : 'tickets_author_unsubscribed';

			return $this->success($this->modx->lexicon($message));
		}

		return $this->error($this->modx->lexicon('ticket_err_wrong_author'));
	}

	/**
	 * Loads an instance of pdoTools.
	 *
	 * @return bool
	 */
	public function loadPdoTools()
	{
		if (!is_object($this->pdoTools) || !($this->pdoTools instanceof \ModxPro\PdoTools\Fetch)) {
			$this->pdoTools = $this->modx->services->get('pdoFetch');
			$this->pdoTools->setConfig($this->config);
		}

		return !empty($this->pdoTools) && $this->pdoTools instanceof \ModxPro\PdoTools\Fetch;
	}

	/**
	 * Process and return the output from a Chunk by name.
	 *
	 * @param string $name       the name of the chunk
	 * @param array  $properties an associative array of properties to process the Chunk with, treated as placeholders within the scope of the Element
	 * @param bool   $fastMode   if false, all MODX tags in chunk will be processed
	 *
	 * @return string the processed output of the Chunk
	 */
	public function getChunk($name, array $properties = [], $fastMode = false)
	{
		if (!$this->modx->parser) {
			$this->modx->getParser();
		}
		if (!$this->pdoTools) {
			$this->loadPdoTools();
		}

		return $this->pdoTools->getChunk($name, $properties, $fastMode);
	}

	/**
	 * Formats date to "10 minutes ago" or "Yesterday in 22:10"
	 * This algorithm taken from https://github.com/livestreet/livestreet/blob/7a6039b21c326acf03c956772325e1398801c5fe/engine/modules/viewer/plugs/function.date_format.php.
	 *
	 * @param string $date       Timestamp to format
	 * @param string $dateFormat
	 *
	 * @return string
	 */
	public function dateFormat($date, $dateFormat = null)
	{
		$date       = preg_match('/^\d+$/', $date) ? $date : strtotime($date);
		$dateFormat = !empty($dateFormat) ? $dateFormat : $this->config['dateFormat'];
		$current    = time();
		$delta      = $current - $date;

		if ($this->config['dateNow'] && $delta < $this->config['dateNow']) {
			return $this->modx->lexicon('ticket_date_now');
		}

		if ($this->config['dateMinutes']) {
			$minutes = round($delta / 60);
			if ($minutes < $this->config['dateMinutes'] && $minutes > 0) {
				return $this->declension($minutes, $this->modx->lexicon('ticket_date_minutes_back', ['minutes' => $minutes]));
			}

			return $this->modx->lexicon('ticket_date_minutes_back_less');
		}

		if ($this->config['dateHours']) {
			$hours = round($delta / 3600);
			if ($hours < $this->config['dateHours'] && $hours > 0) {
				return $this->declension($hours, $this->modx->lexicon('ticket_date_hours_back', ['hours' => $hours]));
			}

			return $this->modx->lexicon('ticket_date_hours_back_less');
		}

		if ($this->config['dateDay']) {
			switch (date('Y-m-d', $date)) {
				case date('Y-m-d'):
					$day = $this->modx->lexicon('ticket_date_today');
					break;
				case date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'))):
					$day = $this->modx->lexicon('ticket_date_yesterday');
					break;
				case date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'))):
					$day = $this->modx->lexicon('ticket_date_tomorrow');
					break;
				default:
					$day = null;
			}
			if ($day) {
				$format = str_replace('day', preg_replace("#(\w{1})#", '\\\${1}', $day), $this->config['dateDay']);

				return date($format, $date);
			}
		}

		$m         = date('n', $date);
		$month_arr = json_decode($this->modx->lexicon('ticket_date_months'), true);
		$month     = $month_arr[$m - 1];

		$format = preg_replace('~(?<!\\\\)F~U', preg_replace('~(\w{1})~u', '\\\${1}', $month), $dateFormat);

		return date($format, $date);
	}

	/**
	 * Declension of words
	 * This algorithm taken from https://github.com/livestreet/livestreet/blob/eca10c0186c8174b774a2125d8af3760e1c34825/engine/modules/viewer/plugs/modifier.declension.php.
	 *
	 * @param int    $count
	 * @param string $forms
	 * @param string $lang
	 *
	 * @return string
	 */
	public function declension($count, $forms, $lang = null)
	{
		if (empty($lang)) {
			$lang = $this->modx->getOption('cultureKey', null, 'en');
		}
		$forms = json_decode($forms, true);

		if ('ru' == $lang) {
			$mod100 = $count % 100;
			switch ($count % 10) {
				case 1:
					if (11 == $mod100) {
						$text = $forms[2];
					} else {
						$text = $forms[0];
					}
					break;
				case 2:
				case 3:
				case 4:
					if (($mod100 > 10) && ($mod100 < 20)) {
						$text = $forms[2];
					} else {
						$text = $forms[1];
					}
					break;
				case 5:
				case 6:
				case 7:
				case 8:
				case 9:
				case 0:
				default:
					$text = $forms[2];
			}
		} else {
			if (1 == $count) {
				$text = $forms[0];
			} else {
				$text = $forms[1];
			}
		}

		return $text;
	}

	/**
	 * Logs user views of a Resource. Need for new comments feature.
	 *
	 * @param int $resource An id of resource
	 */
	public function logView($resource)
	{
		$key = 'Tickets_User';

		if (!$this->authenticated) {
			if (!$this->modx->getOption('tickets.count_guests', false)) {
				return;
			}
			$guest_key = $_SESSION[$key];
		} else {
			if (!empty($_SESSION[$key])) {
				$table = $this->modx->getTableName(TicketView::class);
				$this->modx->exec("DELETE FROM {$table} WHERE `uid` = 0 AND `guest_key` = '{$_SESSION[$key]}' AND `parent` = {$resource};");
			}
			$guest_key = '';
		}

		$key = [
			'uid'       => $this->modx->user->get('id'),
			'guest_key' => $guest_key,
			'parent'    => $resource,
		];
		if (!$view = $this->modx->getObject(TicketView::class, $key)) {
			$view = $this->modx->newObject(TicketView::class);
			$view->fromArray($key, '', true, true);
		}
		$view->set('timestamp', date('Y-m-d H:i:s'));
		$view->save();
	}

	/**
	 * Generate captcha and set it to session.
	 *
	 * @return array
	 */
	public function getCaptcha()
	{
		$min = !empty($this->config['minCaptcha'])
			? (int) $this->config['minCaptcha']
			: 1;
		$max = !empty($this->config['maxCaptcha'])
			? (int) $this->config['maxCaptcha']
			: 10;
		$a                                     = mt_rand($min, $max);
		$b                                     = mt_rand($min, $max);
		$_SESSION['TicketComments']['captcha'] = $a + $b;

		return ['a' => $a, 'b' => $b];
	}

	/**
	 * Upload file for ticket.
	 *
	 * @param string $class
	 *
	 * @return array|string
	 */
	public function fileUpload($data, $class = 'Ticket')
	{
		if (!$this->authenticated || empty($this->config['allowFiles'])) {
			return $this->error('ticket_err_access_denied');
		}

		$data['source'] = $this->config['source'];
		$data['class']  = $class;

		/** @var ProcessorResponse $response */
		$response = $this->runProcessor(Processors\Web\File\Upload::class, $data);
		if ($response->isError()) {
			return $this->error($response->getMessage());
		}
		$file         = $response->getObject();
		$file['size'] = round($file['size'] / 1024, 2);
		$file['new']  = empty($file['new']);

		$tpl  = 'image' == $file['type'] ? $this->config['tplImage'] : $this->config['tplFile'];
		$html = $this->getChunk($tpl, $file);

		return $this->success('', $html);
	}

	/**
	 * Upload file for ticket comment.
	 *
	 * @param string $class
	 *
	 * @return array|string
	 */
	public function fileUploadComment($data, $class = TicketComment::class)
	{
		$data['source'] = $this->config['source'];
		$data['class']  = $class;

		/** @var ProcessorResponse $response */
		$response = $this->runProcessor(Processors\Web\File\UploadComment::class, $data);
		if ($response->isError()) {
			return $this->error($response->getMessage());
		}
		$file         = $response->getObject();
		$file['size'] = round($file['size'] / 1024, 2);
		$file['new']  = empty($file['new']);

		$tpl  = 'image' == $file['type'] ? $this->config['tplImage'] : $this->config['tplFile'];
		$html = $this->getChunk($tpl, $file);

		return $this->success('', $html);
	}

	/**
	 * Delete or restore uploaded file.
	 *
	 * @return array|string
	 */
	public function fileDelete($id)
	{
		if (!$this->authenticated || empty($this->config['allowFiles'])) {
			return $this->error('ticket_err_access_denied');
		}
		/** @var ProcessorResponse $response */
		$response = $this->runProcessor(Processors\Web\File\Delete::class, ['id' => $id]);
		if ($response->isError()) {
			return $this->error($response->getMessage());
		}

		return $this->success();
	}

	/**
	 * Sort uploaded files.
	 *
	 * @return array|string
	 */
	public function fileSort($rank)
	{
		if (!$this->authenticated) {
			return $this->error('ticket_err_access_denied');
		}
		$response = $this->runProcessor(Processors\Web\File\Sort::class, ['rank' => $rank]);
		if ($response->isError()) {
			return $this->error($response->getMessage());
		}

		return $this->success();
	}

	/**
	 * This method returns an error of the cart.
	 *
	 * @param string $message      A lexicon key for error message
	 * @param array  $data         Additional data
	 * @param array  $placeholders Array with placeholders for lexicon entry
	 *
	 * @return array|string $response
	 */
	public function error($message = '', $data = [], $placeholders = [])
	{
		$response = [
			'success' => false,
			'message' => $this->modx->lexicon($message, $placeholders),
			'data'    => $data,
		];

		return $this->config['json_response'] ? json_encode($response) : $response;
	}

	/**
	 * This method returns an success of the cart.
	 *
	 * @param string $message
	 * @param array  $data
	 * @param array  $placeholders
	 *
	 * @return array|string
	 */
	public function success($message = '', $data = [], $placeholders = [])
	{
		$response = [
			'success' => true,
			'message' => $this->modx->lexicon($message, $placeholders),
			'data'    => $data,
		];

		return $this->config['json_response'] ? json_encode($response) : $response;
	}

	/**
	 * Compares MODX version.
	 *
	 * @param string $version
	 * @param string $dir
	 *
	 * @return bool
	 */
	public function systemVersion($version = '2.3.0', $dir = '>=')
	{
		$this->modx->getVersionData();

		return !empty($this->modx->version) && version_compare($this->modx->version['full_version'], $version, $dir);
	}

	public function loadManagerFiles(modManagerController $controller, array $properties = [])
	{
		$ticketsAssetsUrl = $this->config['assetsUrl'];
		$connectorUrl     = $this->config['connectorUrl'];
		$ticketsCssUrl    = $this->config['cssUrl'] . 'mgr/';
		$ticketsJsUrl     = $this->config['jsUrl'] . 'mgr/';

		if (!empty($properties['config'])) {
			$tmp = [
				'assets_js'     => $ticketsAssetsUrl,
				'connector_url' => $connectorUrl,
			];
			$controller->addHtml('<script type="text/javascript">Tickets.config = ' . json_encode($tmp) . ';</script>');
		}
		if (!empty($properties['utils'])) {
			$controller->addJavascript($ticketsJsUrl . 'tickets.js');
			$controller->addLastJavascript($ticketsJsUrl . 'misc/utils.js');
			$controller->addLastJavascript($ticketsJsUrl . 'misc/combos.js');
		}
		if (!empty($properties['css'])) {
			$controller->addCss($ticketsCssUrl . 'tickets.css');
			$controller->addCss($ticketsCssUrl . 'bootstrap.buttons.css');
		}

		if (!empty($properties['section'])) {
			$controller->addLastJavascript($ticketsJsUrl . 'section/section.common.js');
			$controller->addLastJavascript($ticketsJsUrl . 'ticket/tickets.panel.js');
			$controller->addLastJavascript($ticketsJsUrl . 'ticket/tickets.grid.js');
		}
		if (!empty($properties['subscribe'])) {
			$controller->addLastJavascript($ticketsJsUrl . 'subscribe/subscribes.panel.js');
			$controller->addLastJavascript($ticketsJsUrl . 'subscribe/subscribes.grid.js');
		}
		if (!empty($properties['ticket'])) {
			$controller->addLastJavascript($ticketsJsUrl . 'ticket/ticket.common.js');
		}
		if (!empty($properties['tickets'])) {
			$controller->addLastJavascript($ticketsJsUrl . 'ticket/tickets.panel.js');
			$controller->addLastJavascript($ticketsJsUrl . 'ticket/tickets.grid.js');
		}
		if (!empty($properties['threads'])) {
			$controller->addLastJavascript($ticketsJsUrl . 'thread/threads.panel.js');
			$controller->addLastJavascript($ticketsJsUrl . 'thread/threads.grid.js');
			$controller->addLastJavascript($ticketsJsUrl . 'thread/thread.window.js');
			$controller->addLastJavascript($ticketsJsUrl . 'comment/comments.grid.js');
			$controller->addLastJavascript($ticketsJsUrl . 'comment/comment.window.js');
		}
		if (!empty($properties['comments'])) {
			$controller->addLastJavascript($ticketsJsUrl . 'comment/comments.panel.js');
			$controller->addLastJavascript($ticketsJsUrl . 'comment/comments.grid.js');
			$controller->addLastJavascript($ticketsJsUrl . 'comment/comment.window.js');
		}
		if (!empty($properties['authors'])) {
			$controller->addLastJavascript($ticketsJsUrl . 'author/authors.panel.js');
			$controller->addLastJavascript($ticketsJsUrl . 'author/authors.grid.js');
		}
	}
}
