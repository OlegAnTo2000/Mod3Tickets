<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->paths([
		__DIR__ . '/core/components/tickets',
	]);

	// Автоматически подгоняет код под конкретную версию PHP
	$rectorConfig->sets([
		LevelSetList::UP_TO_PHP_81,
		SetList::CODE_QUALITY,
		SetList::TYPE_DECLARATION,
	]);

	// На всякий случай исключим служебные каталоги
	$rectorConfig->skip([
		__DIR__ . '/vendor/*',
	]);
};
