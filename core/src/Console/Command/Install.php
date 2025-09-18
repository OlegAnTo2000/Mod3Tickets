<?php

namespace Tickets\Console\Command;

use Tickets\App;
use MODX\Revolution\modX;
use MMX\Database\Models\Menu;
use MMX\Database\Models\Category;
use MMX\Database\Models\Namespaces;
use MMX\Database\Models\SystemSetting;
use MMX\Database\Models\Plugin;
use MMX\Database\Models\PluginEvent;
use MMX\Database\Models\Snippet;
use MMX\Database\Models\Chunk;
use Illuminate\Database\Eloquent\Model;
use MODX\Revolution\modAccessPolicy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends Command
{
	protected static $defaultName = 'install';
	protected static $defaultDescription = 'Install composer extra for MODX 3';
	protected modX $modx;

	public function __construct(modX $modx, ?string $name = null)
	{
		parent::__construct($name);
		$this->modx = $modx;
	}

	public function run(InputInterface $input, OutputInterface $output): void
	{
		$vendorPath = App::VENDOR_PATH;
		$corePath = App::CORE_PATH;
		$assetsPath = App::ASSETS_PATH;

		$this->createSymlinks($vendorPath, $corePath, $assetsPath, $output);

		$db = new \MMX\Database\App($this->modx);

		// namespace
		$namespace = $this->createNamespace($db);
		$output->writeln("<info>Created namespace $namespace->name</info>");

		// menu
		$menu = $this->createMenu($db);
		$output->writeln("<info>Created menu $menu->text</info>");

		// category
		$category = $this->createCategory($db);
		$output->writeln("<info>Created category $category->name</info>");

		// system settings
		$this->createSystemSettings($db);
		$output->writeln("<info>Created system settings</info>");

		// plugins
		$this->createPlugins($db);
		$output->writeln("<info>Created plugins</info>");

		// snippets
		$this->createSnippets($db);
		$output->writeln("<info>Created snippets</info>");

		// chunks
		$this->createChunks($db);
		$output->writeln("<info>Created chunks</info>");

		// policies
		$this->createPolicies($db);
		$output->writeln("<info>Created access policies</info>");

		$this->modx->getCacheManager()->refresh();
		$output->writeln('<info>Cleared MODX cache</info>');
	}

	protected function createSymlinks(string $vendorPath, string $corePath, string $assetsPath, OutputInterface $output): void
	{
		if (!is_dir($vendorPath . '/core')) {
			$output->writeln("<error>Vendor path for core does not exist</error>");
			throw new \Exception("Vendor path for core does not exist");
		}
		if (!is_dir($vendorPath . '/assets')) {
			$output->writeln("<error>Vendor path for assets does not exist</error>");
			throw new \Exception("Vendor path for assets does not exist");
		}
		if (!is_dir($corePath)) {
			if (symlink($vendorPath . '/core', $corePath)) {
				$output->writeln("<info>Created symlink to $vendorPath/core in $corePath</info>");
			} else {
				$output->writeln("<error>Failed to create symlink to $vendorPath/core in $corePath</error>");
			}
		}
		if (!is_dir($assetsPath)) {
			if (symlink($vendorPath . '/assets', $assetsPath)) {
				$output->writeln("<info>Created symlink to $vendorPath/assets in $assetsPath</info>");
			} else {
				$output->writeln("<error>Failed to create symlink to $vendorPath/assets in $assetsPath</error>");
			}
		}
	}

	protected function createNamespace(\MMX\Database\App $db): Namespaces
	{
		Model::unguard();
		$namespace = Namespaces::updateOrCreate([
			'name' => App::NAME,
		], [
			'path'        => '{core_path}components/' . App::NAME . '/',
			'assets_path' => '{assets_path}components/' . App::NAME . '/',
		]);
		Model::reguard();
		return $namespace;
	}

	protected function createMenu(\MMX\Database\App $db): Menu
	{
		Model::unguard();
		$menu = Menu::updateOrCreate([
			'text' => App::NAME,
		], [
			'text'        => App::NAME,
			'parent'      => 'components',
			'action'      => 'home',
			'description' => App::NAME . '_menu_desc',
			'icon'        => '',
			'menuindex'   => 0,
			'params'      => '',
			'handler'     => '',
			'permissions' => '',
			'namespace'   => App::NAME,
		]);
		Model::reguard();
		return $menu;
	}

	protected function createCategory(\MMX\Database\App $db): Category
	{
		Model::unguard();
		$category = Category::updateOrCreate([
			'category' => App::NAME,
		], [
			'category' => App::NAME,
			'parent'   => 0,
			'rank'     => 0,
		]);
		Model::reguard();
		return $category;
	}

	protected function createSystemSettings(\MMX\Database\App $db): void
	{
		Model::unguard();

		$settings = [
			'mgr_tree_icon_ticketssection' => [
				'xtype' => 'textfield',
				'value' => 'icon icon-comments-o',
				'area'  => 'tickets.main',
			],
			'mgr_tree_icon_ticket' => [
				'xtype' => 'textfield',
				'value' => 'icon icon-comment-o',
				'area'  => 'tickets.main',
			],
			'date_format' => [
				'xtype' => 'textfield',
				'value' => '%d.%m.%y <small>%H:%M</small>',
				'area'  => 'tickets.main',
			],
			'enable_editor' => [
				'xtype' => 'combo-boolean',
				'value' => true,
				'area'  => 'tickets.main',
			],
			'frontend_css' => [
				'value' => '[[+cssUrl]]web/default.css',
				'xtype' => 'textfield',
				'area'  => 'tickets.main',
			],
			'frontend_js' => [
				'value' => '[[+jsUrl]]web/default.js',
				'xtype' => 'textfield',
				'area'  => 'tickets.main',
			],
			'editor_config.ticket' => [
				'xtype' => 'textarea',
				'value' => '{onTab: {keepDefault:false, replaceWith:"	"},
				markupSet: [
					{name:"Bold", className: "btn-bold", key:"B", openWith:"<b>", closeWith:"</b>" },
					{name:"Italic", className: "btn-italic", key:"I", openWith:"<i>", closeWith:"</i>"  },
					{name:"Underline", className: "btn-underline", key:"U", openWith:"<u>", closeWith:"</u>" },
					{name:"Stroke through", className: "btn-stroke", key:"S", openWith:"<s>", closeWith:"</s>" },
					{separator:"---------------" },
					{name:"Bulleted List", className: "btn-bulleted", openWith:"	<li>", closeWith:"</li>", multiline:true, openBlockWith:"<ul>\n", closeBlockWith:"\n</ul>"},
					{name:"Numeric List", className: "btn-numeric", openWith:"	<li>", closeWith:"</li>", multiline:true, openBlockWith:"<ol>\n", closeBlockWith:"\n</ol>"},
					{separator:"---------------" },
					{name:"Quote", className: "btn-quote", openWith:"<blockquote>", closeWith:"</blockquote>"},
					{name:"Code", className: "btn-code", openWith:"<code>", closeWith:"</code>"},
					{name:"Link", className: "btn-link", openWith:"<a href=\"[![Link:!:http://]!]\">", closeWith:"</a>" },
					{name:"Picture", className: "btn-picture", replaceWith:"<img src=\"[![Source:!:http://]!]\" />" },
					{separator:"---------------" },
					{name:"Cut", className: "btn-cut", openWith:"<cut/>" }
				]}',
				'area' => 'tickets.ticket',
			],
			'editor_config.comment' => [
				'xtype' => 'textarea',
				'value' => '{onTab: {keepDefault:false, replaceWith:"	"},
				markupSet: [
					{name:"Bold", className: "btn-bold", key:"B", openWith:"<b>", closeWith:"</b>" },
					{name:"Italic", className: "btn-italic", key:"I", openWith:"<i>", closeWith:"</i>"  },
					{name:"Underline", className: "btn-underline", key:"U", openWith:"<u>", closeWith:"</u>" },
					{name:"Stroke through", className: "btn-stroke", key:"S", openWith:"<s>", closeWith:"</s>" },
					{separator:"---------------" },
					{name:"Quote", className: "btn-quote", openWith:"<blockquote>", closeWith:"</blockquote>"},
					{name:"Code", className: "btn-code", openWith:"<code>", closeWith:"</code>"},
					{name:"Link", className: "btn-link", openWith:"<a href=\"[![Link:!:http://]!]\">", closeWith:"</a>" },
					{name:"Picture", className: "btn-picture", replaceWith:"<img src=\"[![Source:!:http://]!]\" />" }
				]}',
				'area' => 'tickets.comment',
			],
			'default_template' => [
				'xtype' => 'modx-combo-template',
				'value' => '',
				'area' => 'tickets.ticket',
			],
			'snippet_prepare_comment' => [
				'xtype' => 'textfield',
				'value' => '',
				'area' => 'tickets.comment',
			],
			'comment_edit_time' => [
				'xtype' => 'numberfield',
				'value' => 600,
				'area' => 'tickets.comment',
			],
			'clear_cache_on_comment_save' => [
				'xtype' => 'combo-boolean',
				'value' => false,
				'area' => 'tickets.comment',
			],
			'private_ticket_page' => [
				'xtype' => 'numberfield',
				'value' => 0,
				'area' => 'tickets.ticket',
			],
			'unpublished_ticket_page' => [
				'xtype' => 'numberfield',
				'value' => 0,
				'area' => 'tickets.ticket',
			],
			'ticket_max_cut' => [
				'xtype' => 'numberfield',
				'value' => 1000,
				'area'  => 'tickets.ticket',
			],
			'mail_from' => [
				'xtype' => 'textfield',
				'value' => '',
				'area' => 'tickets.mail',
			],
			'mail_from_name' => [
				'xtype' => 'textfield',
				'value' => '',
				'area' => 'tickets.mail',
			],
			'mail_queue' => [
				'xtype' => 'combo-boolean',
				'value' => false,
				'area' => 'tickets.mail',
			],
			'mail_bcc' => [
				'xtype' => 'textfield',
				'value' => '',
				'area' => 'tickets.mail',
			],
			'mail_bcc_level' => [
				'xtype' => 'numberfield',
				'value' => 1,
				'area' => 'tickets.mail',
			],
			'section_content_default' => [
				'value' => '',
				'xtype' => 'textarea',
				'area' => 'tickets.section',
			],
			'source_default' => [
				'value' => 0,
				'xtype' => 'modx-combo-source',
				'area' => 'tickets.main',
			],
			'count_guests' => [
				'xtype' => 'combo-boolean',
				'value' => false,
				'area' => 'tickets.ticket',
			],
			'max_files_upload' => [
				'xtype' => 'numberfield',
				'value' => 0,
				'area' => 'tickets.ticket',
			],
		];

		foreach ($settings as $key => $data) {
			SystemSetting::updateOrCreate([
				'key' => strtolower(App::NAME) . '.' . $key,
			], array_merge([
				'namespace' => strtolower(App::NAME),
			], $data));
		}

		Model::reguard();
	}

	protected function createPlugins(\MMX\Database\App $db): void
	{
		Model::unguard();

		$plugin = Plugin::updateOrCreate([
			'name' => App::NAME,
		], [
			'name'        => App::NAME,
			'description' => '',
			'plugincode'  => $this->getFileContent('plugins/plugin.tickets.php'),
			'static'      => false,
			'source'      => 0,
			'static_file' => '', //'core/components/' . strtolower(App::NAME) . '/elements/plugins/plugin.tickets.php',
			'category'    => $this->getCategoryId(),
		]);

		// Создаем события плагина
		$events = [
			'OnDocFormSave',
			'OnSiteRefresh',
			'OnWebPagePrerender',
			'OnPageNotFound',
			'OnLoadWebDocument',
			'OnWebPageComplete',
			'OnEmptyTrash',
			'OnUserSave',
		];

		foreach ($events as $event) {
			PluginEvent::updateOrCreate([
				'pluginid' => $plugin->id,
				'event'    => $event,
			], [
				'priority'    => 0,
				'propertyset' => 0,
			]);
		}

		Model::reguard();
	}

	protected function createSnippets(\MMX\Database\App $db): void
	{
		Model::unguard();

		$snippets = [
			'TicketForm'         => 'ticket_form',
			'TicketComments'     => 'comments',
			'TicketLatest'       => 'ticket_latest',
			'TicketMeta'         => 'ticket_meta',
			'getTickets'         => 'get_tickets',
			'getTicketsSections' => 'get_sections',
			'getComments'        => 'get_comments',
			'getStars'           => 'get_stars',
			'subscribeAuthor'    => 'subscribe_author',
		];

		foreach ($snippets as $name => $file) {
			Snippet::updateOrCreate([
				'name' => $name,
			], [
				'name'        => $name,
				'description' => '',
				'snippet'     => $this->getFileContent('snippets/snippet.' . $file . '.php'),
				'static'      => false,
				'source'      => 0,
				'static_file' => '', //'core/components/' . strtolower(App::NAME) . '/elements/snippets/snippet.' . $file . '.php',
				'category'    => $this->getCategoryId(),
				'properties'  => $this->getSnippetProperties($file),
			]);
		}

		Model::reguard();
	}

	protected function createChunks(\MMX\Database\App $db): void
	{
		Model::unguard();

		$chunks = [
			'tpl.Tickets.form.create'                => 'form_create',
			'tpl.Tickets.form.update'                => 'form_update',
			'tpl.Tickets.form.preview'               => 'form_preview',
			'tpl.Tickets.form.files'                 => 'form_files',
			'tpl.Tickets.form.file'                  => 'form_file',
			'tpl.Tickets.form.image'                 => 'form_image',
			'tpl.Tickets.comment.form.files'         => 'comment_form_files',
			'tpl.Tickets.ticket.latest'              => 'ticket_latest',
			'tpl.Tickets.ticket.email.bcc'           => 'ticket_email_bcc',
			'tpl.Tickets.ticket.email.subscription'  => 'ticket_email_subscription',
			'tpl.Tickets.comment.form'               => 'comment_form',
			'tpl.Tickets.comment.form.guest'         => 'comment_form_guest',
			'tpl.Tickets.comment.one.auth'           => 'comment_one_auth',
			'tpl.Tickets.comment.one.guest'          => 'comment_one_guest',
			'tpl.Tickets.comment.one.deleted'        => 'comment_one_deleted',
			'tpl.Tickets.comment.wrapper'            => 'comment_wrapper',
			'tpl.Tickets.comment.login'              => 'comment_login',
			'tpl.Tickets.comment.latest'             => 'comment_latest',
			'tpl.Tickets.comment.email.owner'        => 'comment_email_owner',
			'tpl.Tickets.comment.email.reply'        => 'comment_email_reply',
			'tpl.Tickets.comment.email.subscription' => 'comment_email_subscription',
			'tpl.Tickets.comment.email.bcc'          => 'comment_email_bcc',
			'tpl.Tickets.comment.email.unpublished'  => 'comment_email_unpublished',
			'tpl.Tickets.comment.list.row'           => 'comment_list_row',
			'tpl.Tickets.list.row'                   => 'ticket_list_row',
			'tpl.Tickets.sections.row'               => 'ticket_sections_row',
			'tpl.Tickets.sections.wrapper'           => 'ticket_sections_wrapper',
			'tpl.Tickets.meta'                       => 'ticket_meta',
			'tpl.Tickets.meta.file'                  => 'ticket_meta_file',
			'tpl.Tickets.author.subscribe'           => 'ticket_author_subscribe',
			'tpl.Tickets.author.email.subscription'  => 'author_email_subscription',
		];

		foreach ($chunks as $name => $file) {
			Chunk::updateOrCreate([
				'name' => $name,
			], [
				'name'        => $name,
				'description' => '',
				'snippet'     => $this->getFileContent('chunks/chunk.' . $file . '.tpl'),
				'static'      => false,
				'source'      => 0,
				'static_file' => '', //'core/components/' . strtolower(App::NAME) . '/elements/chunks/chunk.' . $file . '.tpl',
				'category'    => $this->getCategoryId(),
			]);
		}

		Model::reguard();
	}

	protected function getFileContent(string $path): string
	{
		$fullPath = MODX_CORE_PATH . 'components/' . strtolower(App::NAME) . '/elements/' . $path;
		return file_exists($fullPath) ? file_get_contents($fullPath) : '';
	}

	protected function getCategoryId(): int
	{
		$category = Category::where('category', App::NAME)->first();
		return $category ? $category->id : 0;
	}

	protected function getSnippetProperties(string $file): array
	{
		$propertiesPath = MODX_CORE_PATH . 'components/' . strtolower(App::NAME) . '/install/data/properties.' . $file . '.php';
		if (file_exists($propertiesPath)) {
			return include $propertiesPath;
		}
		return [];
	}

	protected function createPolicies(\MMX\Database\App $db): void
	{
		$policies = [
			'TicketUserPolicy' => [
				'description' => 'A policy for create and update Tickets.',
				'data' => [
					'ticket_delete'       => true,
					'ticket_publish'      => true,
					'ticket_save'         => true,
					'ticket_vote'         => true,
					'ticket_star'         => true,
					'section_unsubscribe' => true,
					'comment_save'        => true,
					'comment_delete'      => true,
					'comment_remove'      => true,
					'comment_publish'     => true,
					'comment_file_upload' => true,
					'comment_vote'        => true,
					'comment_star'        => true,
					'ticket_file_upload'  => true,
					'ticket_file_delete'  => true,
					'thread_close'        => true,
					'thread_delete'       => true,
					'thread_remove'       => true,
				],
			],
			'TicketSectionPolicy' => [
				'description' => 'A policy for add tickets in section.',
				'data' => [
					'section_add_children' => true,
				],
			],
			'TicketVipPolicy' => [
				'description' => 'A policy for create and update private Tickets.',
				'data' => [
					'ticket_delete'       => true,
					'ticket_publish'      => true,
					'ticket_save'         => true,
					'ticket_vote'         => true,
					'ticket_star'         => true,
					'section_unsubscribe' => true,
					'comment_save'        => true,
					'comment_delete'      => true,
					'comment_remove'      => true,
					'comment_publish'     => true,
					'comment_file_upload' => true,
					'comment_vote'        => true,
					'comment_star'        => true,
					'ticket_view_private' => true,
					'ticket_file_upload'  => true,
					'ticket_file_delete'  => true,
					'thread_close'        => true,
					'thread_delete'       => true,
					'thread_remove'       => true,
				],
			],
		];

		foreach ($policies as $name => $data) {
			$policy = $this->modx->getObject(modAccessPolicy::class, ['name' => $name]);
			if (!$policy) {
				$policy = $this->modx->newObject(modAccessPolicy::class);
				$policy->set('name', $name);
			}

			$policy->set('description', $data['description']);
			$policy->set('data', json_encode($data['data']));
			$policy->set('lexicon', strtolower(App::NAME) . ':permissions');
			$policy->save();
		}
	}
}
