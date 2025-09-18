<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use Phinx\Migration\AbstractMigration;

final class Test extends AbstractMigration
{
	public function up(): void
	{
		$schema = Manager::schema();
		$schema->create(
			'tickets_test',
			static function (Blueprint $table) {
				$table->id();
				$table->string('title')->unique();
				$table->timestamps();
			}
		);
	}

	public function down(): void
	{
		$schema = Manager::schema();
		$schema->drop('tickets_test');
	}
}
