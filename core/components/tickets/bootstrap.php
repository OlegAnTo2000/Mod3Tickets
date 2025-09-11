<?php
/** @var \MODX\Revolution\modX $modx */
$modx->addPackage('Tickets', __DIR__ . '/src/', null, 'Tickets\\');
$modx->services->add('Tickets', function($c) use ($modx) {
  return new Tickets\Tickets($modx);
});