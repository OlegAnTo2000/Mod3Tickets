{if $_modx->user.id && $_modx->user.isAuthenticated('web')}
<span class="author-subscribe pull-right">
    <label class="checkbox">
        <input type="checkbox" name="" id="tickets-author-subscribe" 
				value="1" data-id="{$author_id}"
        {if $subscribed?}checked{/if}/> 
				{$_modx->lexicon('tickets_author_notify')}
    </label>
</span>
{/if}