<?php

class TicketCreateManagerController extends ResourceCreateManagerController
{
	/** @var TicketsSection */
	public $parent;
	/** @var Ticket */
	public $resource;

	/**
	 * Returns language topics.
	 *
	 * @return array
	 */
	public function getLanguageTopics()
	{
		return ['resource', 'tickets:default'];
	}

	/**
	 * Return the default template for this resource.
	 *
	 * @return int
	 */
	public function getDefaultTemplate()
	{
		$properties = $this->parent->getProperties();

		return $properties['template'];
	}

	/**
	 * Register custom CSS/JS for the page.
	 */
	public function loadCustomCssJs()
	{
		$html = $this->head['html'];
		parent::loadCustomCssJs();
		$this->head['html'] = $html;

		if (\is_null($this->resourceArray['properties'])) {
			$this->resourceArray['properties'] = [];
		}
		$properties = $this->parent->getProperties('tickets');
		$this->resourceArray = \array_merge($this->resourceArray, $properties);
		$this->resourceArray['properties']['tickets'] = $properties;

		/** @var Tickets $Tickets */
		$Tickets = tickets_service();
		$Tickets->loadManagerFiles($this, [
			'config' => true,
			'utils' => true,
			'css' => true,
			'ticket' => true,
		]);
		$this->addLastJavascript($Tickets->config['jsUrl'] . 'mgr/ticket/create.js');
		$this->addLastJavascript($Tickets->config['jsUrl'] . 'mgr/misc/strftime-min-1.3.js');

		$ready = [
			'xtype' => 'tickets-page-ticket-create',
			'record' => $this->resourceArray,
			'publish_document' => (int) $this->canPublish,
			'canSave' => (int) $this->canSave,
			'show_tvs' => (int) !empty($this->tvCounts),
			'mode' => 'create',
		];
		$this->addHtml('
        <script type="text/javascript">
        // <![CDATA[
        MODx.config.publish_document = ' . (int) $this->canPublish . ';
        MODx.config.default_template = ' . $this->modx->getOption(
			'tickets.default_template',
			null,
			$this->modx->getOption('default_template'),
			true
		) . ';
        MODx.onDocFormRender = "' . $this->onDocFormRender . '";
        MODx.ctx = "' . $this->ctx . '";
        Ext.onReady(function() {
            MODx.load(' . \json_encode($ready) . ');
        });
        // ]]>
        </script>');

		// load RTE
		$this->loadRichTextEditor();
	}
}
