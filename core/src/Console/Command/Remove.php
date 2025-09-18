<?php

namespace Tickets\Console\Command;

use Tickets\App;
use MODX\Revolution\modX;
use MMX\Database\Models\Namespaces;
use Symfony\Component\Console\Command\Command;
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

		// namespace
		$this->removeNamespace();

		// menu

		// category

		// system settings

		// plugins

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
}
