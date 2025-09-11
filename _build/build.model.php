<?php

use xPDO\Om\xPDOManager;
use MODX\Revolution\modX;
use xPDO\Om\xPDOGenerator;
use MODX\Revolution\modUser;
use MODX\Revolution\Error\modError;
use MODX\Revolution\Transport\modPackageBuilder;

if (!defined('MODX_BASE_PATH')) {
  require 'build.config.php';
}

// Define sources
$root = dirname(dirname(__FILE__)) . '/';
$sources = [
  'root'        => $root,
  'build'       => $root . '_build/',
  'source_core' => $root . 'core/components/' . PKG_NAME_LOWER . '/src',
  'model'       => $root . 'core/components/' . PKG_NAME_LOWER . '/src/Model',
  'schema'      => $root . 'core/components/' . PKG_NAME_LOWER . '/model/schema',
  'xml'         => $root . 'core/components/' . PKG_NAME_LOWER . '/model/schema/' . PKG_NAME_LOWER . '.mysql.schema.xml',
];
unset($root);

/** @noinspection PhpIncludeInspection */
require $sources['build'] . '/includes/functions.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->services->add('error', modError::class);
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

// $modx->services->add('transport', modPackageBuilder::class);

/** @var xPDOManager $manager */
$manager = $modx->getManager();
/** @var xPDOGenerator $generator */
$generator = $manager->getGenerator();

// Remove old model
rrmdir($sources['model'] . PKG_NAME_LOWER . '/mysql');

// Generate a new one
$generator->parseSchema(
  schemaFile: $sources['xml'],
  outputDir: $sources['model'],
  options: [
    'namespacePrefix' => 'Tickets\\Model\\',
    'update'          => 0,
  ]
);

// Add connection to modUser
$data = file_get_contents($sources['model'] . PKG_NAME_LOWER . '/metadata.mysql.php');
$data .= '
$this->map[\''. modUser::class .'\'][\'composites\'][\'AuthorProfile\'] = [
  \'class\'       => \'' . TicketAuthor::class . '\',
  \'local\'       => \'id\',
  \'foreign\'     => \'id\',
  \'cardinality\' => \'one\',
  \'owner\'       => \'local\',
];
';
file_put_contents($sources['model'] . PKG_NAME_LOWER . '/metadata.mysql.php', $data);

$modx->log(modX::LOG_LEVEL_INFO, 'Model generated.');