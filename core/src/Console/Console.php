<?php

namespace Tickets\Console;

use Tickets\App;
use Tickets\Console\Command\Install;
use Tickets\Console\Command\Remove;
use MODX\Revolution\modX;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Console extends Application
{
	protected modX $modx;

	public function __construct(modX $modx)
	{
		parent::__construct(APP::NAME);
		$this->modx = $modx;
	}

	public function doRun(InputInterface $input, OutputInterface $output)
	{
		return parent::doRun($input, $output);
	}

	protected function getDefaultCommands(): array
	{
		return [
			new ListCommand(),
			new Install($this->modx),
			new Remove($this->modx),
		];
	}
}
