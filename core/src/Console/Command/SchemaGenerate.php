<?php

namespace Tickets\Console\Command;

use xPDO\xPDO;
use Tickets\App;
use MODX\Revolution\modX;
use xPDO\Om\xPDOGenerator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchemaGenerate extends Command
{
	protected static $defaultName = 'schema-generate';
	protected static $defaultDescription = 'Generate MODX mysql schema';
	protected modX $modx;

	public function __construct(modX $modx, ?string $name = null)
	{
		parent::__construct($name);
		$this->modx = $modx;
	}

	public function run(InputInterface $input, OutputInterface $output): void
	{
		$root = dirname(__DIR__, 5) . '/';
		$sources = [
			'root'        => $root,
			'source_core' => $root . 'core',
			'src'         => $root . 'core/src',
			'model'       => $root . 'core/src/Model',
			'schema'      => $root . 'core/schema',
			'xml'         => $root . 'core/schema/extra.mysql.schema.xml',
		];
		unset($root);

		// fake connection (dsn / login / pass not important, schema is read directly from xml)
		$xpdo = new xPDO(
			dsn: 'mysql:host=127.0.0.1;dbname=fake;charset=utf8',
			username: 'fake',
			password: 'fake',
			options: [],
			driverOptions: []
		);

		$xpdo->setLogLevel(xPDO::LOG_LEVEL_INFO);
		$xpdo->setLogTarget('ECHO');

		// get generator directly
		$manager   = $xpdo->getManager();
		$generator = $manager->getGenerator();

		// remove old classes
		$pkgModelPath = $sources['model'] . '/mysql';
		if (is_dir($pkgModelPath)) {
			$it = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($pkgModelPath, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ($it as $file) {
				$file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
			}
			rmdir($pkgModelPath);
		}

		// generate new
		$generator->parseSchema(
			$sources['xml'],
			$sources['src'],
			[
				'namespacePrefix' => App::NAMESPACE_PREFIX,
				'update'          => 0,
			]
		);

		$output->writeln('<info>Model generated</info>');

		$this->modx->getCacheManager()->refresh();
		$output->writeln('<info>Cleared MODX cache</info>');
	}
}
