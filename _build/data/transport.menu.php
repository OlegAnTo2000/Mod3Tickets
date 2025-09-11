<?php

use MODX\Revolution\modMenu;
use MODX\Revolution\modX;

$menus = [];

$tmp = [
    'tickets' => [
        'description' => 'ticket_menu_desc',
        'action'      => 'home',
        'icon'        => '<i class="icon icon-large icon-comments-0"></i>',
    ],
];

/** @var modx $modx */
foreach ($tmp as $k => $v) {
    /** @var modMenu $menu */
    $menu = $modx->newObject(modMenu::class);
    $menu->fromArray(array_merge([
        'text' => $k,
        'parent' => 'components',
        'namespace' => PKG_NAME_LOWER,
        'icon' => '',
        'menuindex' => 0,
        'params' => '',
        'handler' => '',
    ], $v), '', true, true);
    $menus[] = $menu;
}
unset($menu);

return $menus;