<li class="ticket-comment" id="comment-{$id}">
    <div class="ticket-comment-body alert alert-warning">
        {$_modx->lexicon('ticket_comment_deleted_text')}
    </div>
    <ol class="comments-list">{$children ?: ''}</ol>
</li>
