{$_modx->lexicon('ticket_comment_email_reply_intro', [
	'name'      => $name,
	'resource'  => $resource,
	'pagetitle' => $pagetitle
])}

<pre>{$text ?: ''}</pre>
<br/><br/>

<a href="{$_modx->makeUrl($resource, '', '', 'full')}#comment-{$id}">
	{$_modx->lexicon('ticket_email_reply')}
</a>
<br/><br/>

{$_modx->lexicon('ticket_comment_email_reply_text')}
<pre>{$parent_text ?: ''}</pre>
