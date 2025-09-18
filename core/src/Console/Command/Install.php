<?php

namespace Tickets\Console\Command;

use Tickets\App;
use MODX\Revolution\modX;
use MMX\Database\Models\Menu;
use MMX\Database\Models\Namespaces;
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

		if (!is_dir($corePath)) {
			symlink($vendorPath . '/core', $corePath);
			$output->writeln('<info>Created symlink for "core"</info>');
		}
		if (!is_dir($assetsPath)) {
			symlink($vendorPath . '/assets', $assetsPath);
			$output->writeln('<info>Created symlink for "assets"</info>');
		}

		$db = new \MMX\Database\App($this->modx);

		// namespace
		$namespace = $this->createNamespace($db);

		// menu
		$this->createMenu($db);


		// category

		// system settings

		// plugins

		$this->modx->getCacheManager()->refresh();
		$output->writeln('<info>Cleared MODX cache</info>');
	}

	protected function createNamespace(\MMX\Database\App $db): Namespaces
	{
		return Namespaces::updateOrCreate([
			'name' => App::NAME,
		], [
			'path'        => '{core_path}components/' . App::NAME . '/',
			'assets_path' => '{assets_path}components/' . App::NAME . '/',
		]);
	}

	protected function createMenu(\MMX\Database\App $db): Menu
	{
		return Menu::updateOrCreate([
			'name' => App::NAME,
		], [
			'path' => '{core_path}components/' . App::NAME . '/',
		]);
	}
}
