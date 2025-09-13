<div class="tickets-latest-row{$guest ?: ''}">
    <span class="user">
			<i class="glyphicon glyphicon-user"></i>
			{$fullname ?: ''}
		</span> 
		<span class="date">{$date_ago ?: ''}</span>
    <br/>
    <span class="ticket">
			<a href="{$_modx->makeUrl($ticket.id, '', '', 'full')}#comment-{$id}">{$ticket.pagetitle ?: ''}</a>
    </span>
    <nobr><i class="glyphicon glyphicon-comment"></i> <span class="comments">{$comments ?: ''}</span></nobr>
</div>
<!--tickets_guest  ticket-comment-guest-->