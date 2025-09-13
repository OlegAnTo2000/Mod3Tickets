{if $_modx->user.id && $_modx->user.isAuthenticated('web')}
	<span class="tickets-subscribe pull-right">
			<label for="tickets-subscribe" class="checkbox">
					<input type="checkbox" name="" id="tickets-subscribe" value="1" data-id="{$_modx->resource.id}"
					{if $subscribed?}checked{/if}/> 
					{$_modx->lexicon('tickets_section_notify')}
			</label>
	</span>
{/if}

<div class="tickets-list">
	{$output}
</div>