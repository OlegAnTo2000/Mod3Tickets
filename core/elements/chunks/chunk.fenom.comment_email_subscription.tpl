{$_modx->lexicon('ticket_comment_email_subscription_intro', [
	'name'      => $name,
	'resource'  => $resource,
	'pagetitle' => $pagetitle
])}

<pre>{$text ?: ''}</pre>
<br/><br/>

<a href="{$_modx->makeUrl($resource, '', '', 'full')}#comment-{$id}">
	{$_modx->lexicon('ticket_email_view')}
</a>
