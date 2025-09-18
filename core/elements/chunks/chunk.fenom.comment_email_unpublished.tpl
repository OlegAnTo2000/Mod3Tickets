{$_modx->lexicon('ticket_comment_email_unpublished_intro', [
	'name'      => $name,
	'resource'  => $resource,
	'pagetitle' => $pagetitle
])}

<pre>{$text ?: ''}</pre>
<br/><br/>

<a href="{$manager_url ?: ''}">
	{$_modx->lexicon('ticket_email_all_comments')}
</a>
