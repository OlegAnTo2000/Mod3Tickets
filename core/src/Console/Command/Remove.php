<?php

namespace Tickets\Console\Command;

use Tickets\App;
use Phinx\Config\Config;
use MODX\Revolution\modX;
use Phinx\Migration\Manager;
use MMX\Database\Models\Namespaces;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Remove extends Command
{
	protected static $defaultName = 'remove';
	protected static $defaultDescription = 'Remove composer extra from MODX 3';
	protected modX $modx;

	public function __construct(modX $modx, ?string $name = null)
	{
		parent::__construct($name);
		$this->modx = $modx;
	}

	public function run(InputInterface $input, OutputInterface $output): void
	{
		$vendorPath = App::VENDOR_PATH;
		$corePath   = App::CORE_PATH;
		$assetsPath = App::ASSETS_PATH;

		$db = new \MMX\Database\App($this->modx);

		// namespace
		$this->removeNamespace();

		// menu

		// category

		// system settings

		// plugins

		// migrations
		$this->removeMigrations($db, $output);

		if (is_dir($corePath)) {
			unlink($corePath);
			$output->writeln('<info>Removed symlink for "core"</info>');
		}
		if (is_dir($assetsPath)) {
			unlink($assetsPath);
			$output->writeln('<info>Removed symlink for "assets"</info>');
		}

		$this->modx->getCacheManager()->refresh();
		$output->writeln('<info>Cleared MODX cache</info>');
	}

	protected function removeNamespace(): void
	{
		Namespaces::query()->where('name', App::NAME)->delete();
	}

	protected function removeMigrations(\MMX\Database\App $db, OutputInterface $output): void
	{
		$phinxMigrationsPath = App::VENDOR_PATH . '/core/db/migrations';
		if (is_dir($phinxMigrationsPath)) {
			rmdir($phinxMigrationsPath);
		}

		$phinxMigrationsPath = App::VENDOR_PATH . '/core/db/migrations';
		$connection = $db->getConnection();

		$configArray = [
			'paths' => [
				'migrations' => $phinxMigrationsPath,
			],
			'environments' => [
				'default_migration_table' => $connection->getConfig()['prefix'] . 'tickets_phinx_migrations',
				'default_environment'     => 'local',
				'local' => [
					'name'       => $connection->getConfig()['database'],
					'connection' => $connection->getPdo(),
				],
			],
		];

		$output->writeln('<info>Run Phinx migrations</info>');

		$config  = new Config($configArray);
		$manager = new Manager($config, new StringInput(' '), $output);

		$manager->rollback('local');
	}
}
