<?php

namespace Tickets\Console\Command;

use Tickets\App;
use MODX\Revolution\modX;
use MMX\Database\Models\Menu;
use MMX\Database\Models\Namespaces;
use Illuminate\Database\Eloquent\Model;
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

		// menu
		// $this->createMenu($db);


		// category

		// system settings

		// plugins

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
			'name' => App::NAME,
		], [
			'text'        => App::NAME,
			'parent'      => 0,
			'action'      => 0,
			'description' => App::NAME,
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
}
