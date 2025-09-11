<?php

use MODX\Revolution\modX;
use MODX\Revolution\modAccessPermission;
use MODX\Revolution\modAccessPolicyTemplate;

$templates = [];

$tmp = [
    'TicketsUserPolicyTemplate' => [
        'description' => 'A policy for users to create Tickets and comments.',
        'template_group' => 1,
        'permissions' => [
            'ticket_delete'       => [],
            'ticket_publish'      => [],
            'ticket_save'         => [],
            'ticket_view_private' => [],
            'ticket_vote'         => [],
            'ticket_star'         => [],
            'section_unsubscribe' => [],
            'comment_save'        => [],
            'comment_delete'      => [],
            'comment_remove'      => [],
            'comment_publish'     => [],
            'comment_file_upload' => [],
            'comment_vote'        => [],
            'comment_star'        => [],
            'ticket_file_upload'  => [],
            'ticket_file_delete'  => [],
            'thread_close'        => [],
            'thread_delete'       => [],
            'thread_remove'       => [],
        ],
    ],
    'TicketsSectionPolicyTemplate' => [
        'description'    => 'A policy for users to add Tickets to section.',
        'template_group' => 3,
        'permissions'    => [
            'section_add_children' => [],
        ],
    ],
];

/** @var modX $modx */
foreach ($tmp as $k => $v) {
    $permissions = [];

    if (isset($v['permissions']) && is_array($v['permissions'])) {
        foreach ($v['permissions'] as $k2 => $v2) {
            /** @var modAccessPermission $permission */
            $permission = $modx->newObject(modAccessPermission::class);
            $permission->fromArray(array_merge([
                'name' => $k2,
                'description' => $k2,
                'value' => true,
            ], $v2), '', true, true);
            $permissions[] = $permission;
        }
    }
    
    /** @var modAccessPolicyTemplate $template */
    $template = $modx->newObject(modAccessPolicyTemplate::class);
    $template->fromArray(array_merge([
        'name' => $k,
        'lexicon' => PKG_NAME_LOWER . ':permissions',
    ], $v), '', true, true);

    if (!empty($permissions)) {
        $template->addMany($permissions);
    }
    $templates[] = $template;
}

return $templates;
