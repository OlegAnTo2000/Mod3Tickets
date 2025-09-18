<form class="well update" method="post" action="" id="ticketForm">
    <div id="ticket-preview-placeholder"></div>

    <input type="hidden" name="tid" value="{$id}"/>

    <div class="form-group">
        <label for="ticket-sections">{$_modx->lexicon('tickets_section')}</label>
        <select name="parent" class="form-control" id="ticket-sections">{$sections}</select>
        <span class="error"></span>
    </div>

    <div class="form-group">
        <label for="ticket-pagetitle">{$_modx->lexicon('ticket_pagetitle')}</label>
        <input type="text" class="form-control" placeholder="{$_modx->lexicon('ticket_pagetitle')}" name="pagetitle"
               value="{$pagetitle}" maxlength="50" id="ticket-pagetitle"/>
        <span class="error"></span>
    </div>

    <div class="form-group">
        <textarea class="form-control" placeholder="{$_modx->lexicon('ticket_content')}" name="content" id="ticket-editor" rows="10">{$content}</textarea>
        <span class="error" id="content-error"></span>
    </div>

    <div class="ticket-form-files">
        {$files}
    </div>

    <div class="form-actions row">
        <div class="col-md-4">
            <input type="button" class="btn btn-default preview" value="{$_modx->lexicon('ticket_preview')}" title="Ctrl + Enter"/>
        </div>
        <div class="col-md-8 move-right">
            {if $published == 1}
							<a href="{$_modx->makeUrl($id, '', '', 'full')}" class="btn btn-default btn-xs" target="_blank">{$_modx->lexicon('ticket_open')}</a>
							<input type="button" class="btn btn-danger draft" name="draft" value="{$_modx->lexicon('ticket_draft')}" title=""/>
            {else}
            	<input type="button" class="btn btn-primary publish" name="publish" value="{$_modx->lexicon('ticket_publish')}" title=""/>
            {/if}
            <input type="submit" class="btn btn-default save" name="save" value="{$_modx->lexicon('ticket_save')}"
                   title="Ctrl + Shift + Enter"/>
            {if $allowDelete == 1}
                {if $deleted == 1}
                	<input type="button" class="btn btn-default undelete" data-confirm="{$_modx->lexicon('ticket_undelete_text')}" name="undelete" value="{$_modx->lexicon('ticket_undelete')}"/>
                {else}
                	<input type="button" class="btn btn-default delete" data-confirm="{$_modx->lexicon('ticket_delete_text')}" name="delete" value="{$_modx->lexicon('ticket_delete')}"/>
                {/if}
            {/if}
        </div>
    </div>
</form>