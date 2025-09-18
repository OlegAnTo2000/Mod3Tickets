<?php

namespace Tickets\Console\Command;

use MODX\Revolution\modX;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Test extends Command
{
	protected static $defaultName = 'test';
	protected static $defaultDescription = 'Test';
	protected modX $modx;

	public function __construct(modX $modx, ?string $name = null)
	{
		parent::__construct($name);
		$this->modx = $modx;
	}

	public function run(InputInterface $input, OutputInterface $output): void
	{
		$output->writeln('<info>Test</info>');
	}
}
