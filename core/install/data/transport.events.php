<?php

use MODX\Revolution\modEvent;
use MODX\Revolution\modX;

$events = [];

$tmp = [
    'OnBeforeCommentSave' => [],
    'OnCommentSave'       => [],

    'OnBeforeCommentPublish'   => [],
    'OnCommentPublish'         => [],
    'OnBeforeCommentUnpublish' => [],
    'OnCommentUnpublish'       => [],
    'OnBeforeCommentDelete'    => [],
    'OnCommentDelete'          => [],
    'OnBeforeCommentUndelete'  => [],
    'OnCommentUndelete'        => [],

    'OnBeforeCommentRemove' => [],
    'OnCommentRemove'       => [],

    'OnBeforeTicketThreadClose'    => [],
    'OnTicketThreadClose'          => [],
    'OnBeforeTicketThreadOpen'     => [],
    'OnTicketThreadOpen'           => [],
    'OnBeforeTicketThreadDelete'   => [],
    'OnTicketThreadDelete'         => [],
    'OnBeforeTicketThreadUndelete' => [],
    'OnTicketThreadUndelete'       => [],

    'OnBeforeTicketThreadRemove' => [],
    'OnTicketThreadRemove'       => [],

    'OnBeforeTicketVote'  => [],
    'OnTicketVote'        => [],
    'OnBeforeCommentVote' => [],
    'OnCommentVote'       => [],

    'OnBeforeTicketStar'    => [],
    'OnTicketStar'          => [],
    'OnBeforeTicketUnStar'  => [],
    'OnTicketUnStar'        => [],
    'OnBeforeCommentStar'   => [],
    'OnCommentStar'         => [],
    'OnBeforeCommentUnStar' => [],
    'OnCommentUnStar'       => [],
];

/** @var modx $modx */
foreach ($tmp as $k => $v) {
    /** @var modEvent $event */
    $event = $modx->newObject(modEvent::class);
    $event->fromArray(array_merge([
        'name' => $k,
        'service' => 6,
        'groupname' => PKG_NAME,
    ], $v), '', true, true);
    $events[] = $event;
}

return $events;