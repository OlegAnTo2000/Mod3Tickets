<?php

namespace Tickets\Processors\Mgr\Ticket;

use function json_decode;

use MODX\Revolution\Processors\Processor;
use MODX\Revolution\Processors\ProcessorResponse;
use Tickets\Tickets;

class Multiple extends Processor
{
	/**
	 * @return array|string
	 */
	public function process()
	{
		if (!$method = $this->getProperty('method', false)) {
			return $this->failure();
		}
		$ids = json_decode($this->getProperty('ids'), true);
		if (empty($ids)) {
			return $this->success();
		}

		/** @var Tickets $Tickets */
		$Tickets = $this->modx->services->get('tickets');

		foreach ($ids as $id) {
			/** @var ProcessorResponse $response */
			$response = $Tickets->runProcessor('mgr/ticket/' . $method, ['id' => $id]);
			if ($response->isError()) {
				return $response->getResponse();
			}
		}

		return $this->success();
	}
}
