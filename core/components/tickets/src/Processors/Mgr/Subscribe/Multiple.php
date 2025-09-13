<?php

namespace Tickets\Processors\Mgr\Subscribe;

use function intval;
use function json_decode;

use MODX\Revolution\Processors\Processor;
use MODX\Revolution\Processors\ProcessorResponse;

use function tickets_service;

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
		$ids     = json_decode($this->getProperty('ids'), true);
		$parents = intval($this->getProperty('parents'));

		if (empty($ids) || empty($parents)) {
			return $this->success();
		}

		/** @var Tickets $Tickets */
		$Tickets = tickets_service();

		/** @var ProcessorResponse $response */
		$response = $Tickets->runProcessor('mgr/subscribe/' . $method, ['ids' => $ids, 'parents' => $parents]);
		if ($response->isError()) {
			return $response->getResponse();
		}

		return $this->success();
	}
}
