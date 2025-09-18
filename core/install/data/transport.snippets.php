<?php

use MODX\Revolution\modX;
use MODX\Revolution\modSnippet;

$snippets = [];

$tmp = [
    'TicketForm'         => 'ticket_form',
    'TicketComments'     => 'comments',
    'TicketLatest'       => 'ticket_latest',
    'TicketMeta'         => 'ticket_meta',
    'getTickets'         => 'get_tickets',
    'getTicketsSections' => 'get_sections',
    'getComments'        => 'get_comments',
    'getStars'           => 'get_stars',
    'subscribeAuthor'    => 'subscribe_author',
];

/** @var modX $modx */
/** @var array $sources */
foreach ($tmp as $k => $v) {
    /** @var modSnippet $snippet */
    $snippet = $modx->newObject(modSnippet::class);
    $snippet->fromArray([
        'name' => $k,
        'description' => '',
        'snippet' => getSnippetContent($sources['source_core'] . '/elements/snippets/snippet.' . $v . '.php'),
        'static' => BUILD_SNIPPET_STATIC,
        'source' => 1,
        'static_file' => 'core/components/' . PKG_NAME_LOWER . '/elements/snippets/snippet.' . $v . '.php',
    ], '', true, true);

    /** @noinspection PhpIncludeInspection */
    $properties = include $sources['build'] . 'properties/properties.' . $v . '.php';
    /** @var array $properties */
    $snippet->setProperties($properties);
    $snippets[] = $snippet;
}
unset($properties);

return $snippets;