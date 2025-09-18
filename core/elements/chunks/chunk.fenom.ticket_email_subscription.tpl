{$_modx->lexicon('ticket_email_subscribed_intro', [
	'id'            => $id,
	'fullname'      => $user.fullname,
	'section'       => $section.id,
	'section_title' => $section.pagetitle,
	'pagetitle'     => $pagetitle
])}

<pre style="background-color:#efefef;">{$introtext}</pre>
<br/><br/>

<a href="{$_modx->makeUrl($id, '', '', 'full')}">{$_modx->lexicon('ticket_email_view')}</a>