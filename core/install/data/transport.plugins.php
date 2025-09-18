<?php

use MODX\Revolution\modX;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modPlugin;

$plugins = [];

$tmp = [
    'Tickets' => [
        'file'        => 'tickets',
        'description' => '',
        'events'      => [
            'OnDocFormSave',
            'OnSiteRefresh',
            'OnWebPagePrerender',
            'OnPageNotFound',
            'OnLoadWebDocument',
            'OnWebPageComplete',
            'OnEmptyTrash',
            'OnUserSave',
        ],
    ],
];

/** @var modX $modx */
/** @var array $sources */
foreach ($tmp as $k => $v) {
    /** @var modPlugin $plugin */
    $plugin = $modx->newObject(modPlugin::class);
    $plugin->fromArray([
        'name'        => $k,
        'description' => @$v['description'],
        'plugincode'  => getSnippetContent($sources['source_core'] . '/elements/plugins/plugin.' . $v['file'] . '.php'),
        'static'      => BUILD_PLUGIN_STATIC,
        'source'      => 1,
        'static_file' => 'core/components/' . PKG_NAME_LOWER . '/elements/plugins/plugin.' . $v['file'] . '.php',
    ], '', true, true);

    $events = [];
    if (!empty($v['events']) && is_array($v['events'])) {
        foreach ($v['events'] as $k2 => $v2) {
            /** @var modPluginEvent $event */
            $event = $modx->newObject(modPluginEvent::class);
            $event->fromArray([
                'event' => $v2,
                'priority' => 0,
                'propertyset' => 0,
            ], '', true, true);
            $events[] = $event;
        }
        unset($v['events']);
    }

    if (!empty($events)) {
        $plugin->addMany($events);
    }
    $plugins[] = $plugin;
}

return $plugins;