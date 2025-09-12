<?php

use xPDO\xPDO;
use xPDO\Om\xPDOGenerator;

error_reporting(E_ALL & ~E_DEPRECATED);
require __DIR__ . '/build.config.php';

// пути
$root = dirname(__DIR__) . '/';
$sources = [
    'root'        => $root,
    'build'       => $root . '_build/',
    'source_core' => $root . 'core/components/' . PKG_NAME_LOWER,
    'src'         => $root . 'core/components/' . PKG_NAME_LOWER . '/src',
    'model'       => $root . 'core/components/' . PKG_NAME_LOWER . '/src/Model',
    'schema'      => $root . 'core/components/' . PKG_NAME_LOWER . '/schema/',
    'xml'         => $root . 'core/components/' . PKG_NAME_LOWER . '/schema/' . PKG_NAME_LOWER . '.mysql.schema.xml',
];
unset($root);

// липовое подключение (dsn / login / pass неважны, схема читается напрямую из xml)
$xpdo = new xPDO(
  dsn: 'mysql:host=127.0.0.1;dbname=fake;charset=utf8',
  username: 'fake',
  password: 'fake',
  options: [],
  driverOptions: []
);

$xpdo->setLogLevel(xPDO::LOG_LEVEL_INFO);
$xpdo->setLogTarget('ECHO');

// получить генератор напрямую
$manager   = $xpdo->getManager();
$generator = $manager->getGenerator();

// снести старые классы
$pkgModelPath = $sources['model'] . '/mysql';
if (is_dir($pkgModelPath)) {
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pkgModelPath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
  );
  foreach ($it as $file) {
    $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
  }
  rmdir($pkgModelPath);
}

// сгенерить новые
$generator->parseSchema(
  $sources['xml'],
  $sources['src'],
  [
    'namespacePrefix' => 'Tickets',
    'update'          => 0,
  ]
);

echo "Model generated.\n";