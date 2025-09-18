<?php

use MODX\Revolution\modX;
use MODX\Revolution\modAccessPolicy;

$policies = [];

$tmp = [
    'TicketUserPolicy' => [
        'description' => 'A policy for create and update Tickets.',
        'data' => [
            'ticket_delete' => true,
            'ticket_publish' => true,
            'ticket_save' => true,
            'ticket_vote' => true,
            'ticket_star' => true,
            'section_unsubscribe' => true,
            'comment_save' => true,
            'comment_delete' => true,
            'comment_remove' => true,
            'comment_publish' => true,
            'comment_file_upload' => true,
            'comment_vote' => true,
            'comment_star' => true,
            'ticket_file_upload' => true,
            'ticket_file_delete' => true,
            'thread_close' => true,
            'thread_delete' => true,
            'thread_remove' => true,
        ],
    ],
    'TicketSectionPolicy' => [
        'description' => 'A policy for add tickets in section.',
        'data' => [
            'section_add_children' => true,
        ],
    ],
    'TicketVipPolicy' => [
        'description' => 'A policy for create and update private Tickets.',
        'data' => [
            'ticket_delete' => true,
            'ticket_publish' => true,
            'ticket_save' => true,
            'ticket_vote' => true,
            'ticket_star' => true,
            'section_unsubscribe' => true,
            'comment_save' => true,
            'comment_delete' => true,
            'comment_remove' => true,
            'comment_publish' => true,
            'comment_file_upload' => true,
            'comment_vote' => true,
            'comment_star' => true,
            'ticket_view_private' => true,
            'ticket_file_upload' => true,
            'ticket_file_delete' => true,
            'thread_close' => true,
            'thread_delete' => true,
            'thread_remove' => true,
        ],
    ],
];

/** @var modX $modx */
foreach ($tmp as $k => $v) {
    if (isset($v['data'])) {
        $v['data'] = json_encode($v['data']);
    }

    /** @var modAccessPolicy $policy */
    $policy = $modx->newObject(modAccessPolicy::class);
    $policy->fromArray(array_merge([
        'name'    => $k,
        'lexicon' => PKG_NAME_LOWER . ':permissions',
    ], $v), '', true, true);

    $policies[] = $policy;
}

return $policies;