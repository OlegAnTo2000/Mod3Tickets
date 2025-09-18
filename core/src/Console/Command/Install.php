<?php

namespace Tickets\Console\Command;

use Tickets\App;
use MODX\Revolution\modX;
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

		// namespace

		// menu

		// category

		// system settings

		// plugins

		$this->modx->getCacheManager()->refresh();
		$output->writeln('<info>Cleared MODX cache</info>');
	}
}
