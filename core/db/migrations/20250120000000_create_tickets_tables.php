<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use Phinx\Migration\AbstractMigration;

final class CreateTicketsTables extends AbstractMigration
{
	public function up(): void
	{
		$schema = Manager::schema();

		// Таблица комментариев к тикетам
		$schema->create(
			'tickets_comments',
			static function (Blueprint $table) {
				$table->id();
				$table->unsignedInteger('thread')->default(0);
				$table->unsignedInteger('parent')->default(0);
				$table->text('text');
				$table->text('raw');
				$table->string('name', 255);
				$table->string('email', 255);
				$table->string('ip', 16)->default('0.0.0.0');
				$table->smallInteger('rating')->nullable()->default(0);
				$table->smallInteger('rating_plus')->nullable()->default(0);
				$table->smallInteger('rating_minus')->nullable()->default(0);
				$table->dateTime('createdon')->nullable();
				$table->unsignedInteger('createdby')->default(0);
				$table->dateTime('editedon')->nullable();
				$table->unsignedInteger('editedby')->default(0);
				$table->boolean('published')->unsigned()->default(true);
				$table->boolean('deleted')->unsigned()->default(false);
				$table->dateTime('deletedon')->nullable();
				$table->unsignedInteger('deletedby')->default(0);
				$table->json('properties')->nullable();

				$table->index('thread');
				$table->index('parent');
				$table->index('deleted');
				$table->index('published');
				$table->index('rating');
			}
		);

		// Таблица потоков обсуждений
		$schema->create(
			'tickets_threads',
			static function (Blueprint $table) {
				$table->id();
				$table->unsignedInteger('resource')->default(0);
				$table->string('name', 191);
				$table->json('subscribers');
				$table->dateTime('createdon')->nullable();
				$table->unsignedInteger('createdby')->default(0);
				$table->boolean('closed')->unsigned()->default(false);
				$table->boolean('deleted')->unsigned()->default(false);
				$table->dateTime('deletedon')->nullable();
				$table->unsignedInteger('deletedby')->default(0);
				$table->unsignedInteger('comment_last')->default(0);
				$table->dateTime('comment_time')->nullable()->index();
				$table->unsignedInteger('comments')->nullable()->default(0);
				$table->json('properties')->nullable();

				$table->unique('name');
				$table->index('resource');
				$table->index('comment_last');
				$table->index('comments');
				$table->index('closed');
			}
		);

		// Таблица голосов
		$schema->create(
			'tickets_votes',
			static function (Blueprint $table) {
				$table->unsignedInteger('id')->default(0);
				$table->string('class', 100);
				$table->unsignedInteger('owner')->default(0);
				$table->tinyInteger('value')->default(0);
				$table->dateTime('createdon')->nullable()->index();
				$table->unsignedInteger('createdby')->default(0);
				$table->string('ip', 16)->nullable()->default('0.0.0.0');

				$table->primary(['id', 'createdby', 'class']);
				$table->index('owner');
			}
		);

		// Таблица звездочек
		$schema->create(
			'tickets_stars',
			static function (Blueprint $table) {
				$table->unsignedInteger('id')->default(0);
				$table->string('class', 100)->default('Ticket');
				$table->unsignedInteger('owner')->default(0);
				$table->dateTime('createdon')->nullable()->index();
				$table->unsignedInteger('createdby')->default(0);

				$table->primary(['id', 'createdby', 'class']);
				$table->index('owner');
			}
		);

		// Таблица просмотров
		$schema->create(
			'tickets_views',
			static function (Blueprint $table) {
				$table->unsignedInteger('parent')->default(0);
				$table->unsignedInteger('uid')->default(0);
				$table->char('guest_key', 32)->default('');
				$table->dateTime('timestamp');

				$table->primary(['parent', 'uid', 'guest_key']);
			}
		);

		// Таблица очереди почтовых уведомлений
		$schema->create(
			'tickets_mail_queues',
			static function (Blueprint $table) {
				$table->id();
				$table->timestamp('timestamp')->useCurrent();
				$table->unsignedInteger('uid')->default(0);
				$table->string('subject', 255)->nullable();
				$table->text('body')->nullable();
				$table->string('email', 255)->nullable();

				$table->index('uid');
				$table->index('email');
			}
		);

		// Таблица файлов
		$schema->create(
			'tickets_files',
			static function (Blueprint $table) {
				$table->id();
				$table->unsignedInteger('parent')->default(0);
				$table->string('class', 100);
				$table->unsignedInteger('source')->nullable()->default(1);
				$table->string('name', 255);
				$table->text('description')->nullable();
				$table->string('path', 255);
				$table->string('file', 255);
				$table->string('type', 50)->nullable();
				$table->unsignedInteger('size')->default(0);
				$table->dateTime('createdon')->nullable();
				$table->unsignedInteger('createdby')->default(0);
				$table->tinyInteger('rank')->unsigned()->nullable()->default(0);
				$table->string('url', 255);
				$table->string('thumb', 255);
				$table->json('thumbs')->nullable();
				$table->boolean('deleted')->nullable()->default(false);
				$table->json('properties')->nullable();
				$table->char('hash', 40)->nullable()->default('')->index();

				$table->index(['parent', 'class']);
				$table->index('source');
				$table->index('type');
				$table->index('deleted');
			}
		);

		// Таблица статистики авторов
		$schema->create(
			'tickets_authors',
			static function (Blueprint $table) {
				$table->unsignedInteger('id')->primary();
				$table->decimal('rating', 12, 2)->nullable()->default(0);
				$table->dateTime('createdon')->nullable();
				$table->dateTime('visitedon')->nullable();
				$table->unsignedInteger('tickets')->nullable()->default(0);
				$table->unsignedInteger('comments')->nullable()->default(0);
				$table->unsignedInteger('views')->nullable()->default(0);
				$table->decimal('votes_tickets', 12, 2)->nullable()->default(0);
				$table->decimal('votes_comments', 12, 2)->nullable()->default(0);
				$table->unsignedInteger('stars_tickets')->nullable()->default(0);
				$table->unsignedInteger('stars_comments')->nullable()->default(0);
				$table->unsignedInteger('votes_tickets_up')->nullable()->default(0);
				$table->unsignedInteger('votes_tickets_down')->nullable()->default(0);
				$table->unsignedInteger('votes_comments_up')->nullable()->default(0);
				$table->unsignedInteger('votes_comments_down')->nullable()->default(0);
				$table->json('properties')->nullable();

				$table->index('rating');
				$table->index('createdon');
				$table->index('visitedon');
				$table->index('tickets');
				$table->index('comments');
				$table->index('views');
				$table->index('votes_tickets');
				$table->index('votes_comments');
				$table->index('stars_tickets');
				$table->index('stars_comments');
			}
		);

		// Таблица действий авторов
		$schema->create(
			'tickets_author_actions',
			static function (Blueprint $table) {
				$table->unsignedInteger('id');
				$table->string('action', 50);
				$table->unsignedInteger('owner')->default(0);
				$table->decimal('rating', 12, 2)->nullable()->default(0);
				$table->unsignedInteger('multiplier')->nullable()->default(1);
				$table->unsignedInteger('ticket')->default(0);
				$table->unsignedInteger('section')->default(0);
				$table->unsignedInteger('createdby')->default(0);
				$table->dateTime('createdon');
				$table->year('year')->nullable()->default(0);
				$table->tinyInteger('month')->nullable()->default(0);
				$table->tinyInteger('day')->nullable()->default(0);

				$table->primary(['id', 'action', 'owner', 'createdby']);
				$table->index('createdon');
				$table->index('section');
				$table->index('ticket');
				$table->index('action');
				$table->index('owner');
				$table->index(['year', 'month', 'day']);
			}
		);

		// Таблица агрегированной статистики
		$schema->create(
			'tickets_totals',
			static function (Blueprint $table) {
				$table->unsignedInteger('id');
				$table->string('class', 100);
				$table->unsignedInteger('tickets')->nullable()->default(0);
				$table->unsignedInteger('comments')->nullable()->default(0);
				$table->unsignedInteger('views')->nullable()->default(0);
				$table->unsignedInteger('stars')->nullable()->default(0);
				$table->smallInteger('rating')->nullable()->default(0);
				$table->smallInteger('rating_plus')->unsigned()->nullable()->default(0);
				$table->smallInteger('rating_minus')->unsigned()->nullable()->default(0);

				$table->primary(['id', 'class']);
				$table->index('views');
				$table->index('comments');
				$table->index('stars');
				$table->index('tickets');
				$table->index('rating');
			}
		);
	}

	public function down(): void
	{
		$schema = Manager::schema();

		$tables = [
			'tickets_totals',
			'tickets_author_actions',
			'tickets_authors',
			'tickets_files',
			'tickets_mail_queues',
			'tickets_views',
			'tickets_stars',
			'tickets_votes',
			'tickets_threads',
			'tickets_comments',
		];

		foreach ($tables as $table) {
			$schema->dropIfExists($table);
		}
	}
}
