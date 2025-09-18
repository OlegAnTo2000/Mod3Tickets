<div class="tickets-latest-row">
    <span class="user"><i class="glyphicon glyphicon-user"></i> {$fullname}</span> <span
            class="date">{$date_ago}</span>
    <br/>
    <span class="section">
        <i class="glyphicon glyphicon-folder-open"></i> <a href="{$_modx->makeUrl($section.id, '', '', 'full')}">{$section.pagetitle}</a> <span
                class="arrow">&rarr;</span>
    </span>
    <span class="ticket">
        <a href="{$_modx->makeUrl($id, '', '', 'full')}">{$pagetitle}</a>
    </span>
    <nobr><i class="glyphicon glyphicon-comment"></i> <span class="comments">{$comments}</span></nobr>
</div>