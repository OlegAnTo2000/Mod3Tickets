<?php

use MODX\Revolution\modExtraManagerController;
use Tickets\Tickets;

class TicketsHomeManagerController extends modExtraManagerController
{
	/**
	 * @return array
	 */
	public function getLanguageTopics()
	{
		return ['tickets:default'];
	}

	/**
	 * @return string|null
	 */
	public function getPageTitle()
	{
		return $this->modx->lexicon('tickets');
	}

	public function loadCustomCssJs()
	{
		/** @var Tickets $Tickets */
		$Tickets = tickets_service();

		$Tickets->loadManagerFiles($this, [
			'config'   => true,
			'utils'    => true,
			'css'      => true,
			'threads'  => true,
			'comments' => true,
			'tickets'  => true,
			'authors'  => true,
		]);
		$this->addLastJavascript($Tickets->config['jsUrl'] . 'mgr/home.js');
		$this->addLastJavascript($Tickets->config['jsUrl'] . 'mgr/misc/strftime-min-1.3.js');
		$this->addHtml('
        <script type="text/javascript">
            Ext.onReady(function() {
                MODx.load({xtype: "tickets-page-home"});
            });
        </script>');
	}

	/**
	 * @return string
	 */
	public function getTemplateFile()
	{
		/** @var Tickets $Tickets */
		$Tickets = tickets_service();

		return $Tickets->config['templatesPath'] . 'home.tpl';
	}
}
