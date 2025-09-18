{$_modx->lexicon('ticket_email_bcc_intro', [
	'fullname'  => $user.fullname,
	'email'     => $user.email,
	'id'        => $id,
	'pagetitle' => $pagetitle
])}

<pre style="background-color:#efefef;">{$introtext}</pre>
<br/><br/>

<a href="{$_modx->makeUrl($id, '', '', 'full')}">{$_modx->lexicon('ticket_email_view')}</a>