<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('project_project_status')) {
            Schema::create('project_project_status', function (Blueprint $table) {
                // Первичный ключ
                $table->ulid('id')->primary();

                // Внешний ключ на проект
                $table->unsignedBigInteger('project_id');
                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

                // Внешний ключ на статус проекта
                $table->ulid('project_status_id');
                $table->foreign('project_status_id')->references('id')->on('project_statuses')->onDelete('cascade');

                // Время назначения статуса
                $table->timestamp('assigned_at')->useCurrent();

                // Кто назначил статус (опционально, может быть ID пользователя)
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

                // Комментарий к назначению статуса
                $table->text('comment')->nullable();

                // Уникальный индекс для предотвращения дублирования
                $table->unique(['project_id', 'project_status_id', 'assigned_at'], 'pps_unique');

                // Временные метки
                $table->timestamps();
            });
        } else {
            // Если таблица уже существует (например, была создана при предыдущей неудачной миграции),
            // проверим наличие уникального индекса и добавим его при необходимости
            $indexExists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::raw('DATABASE()'))
                ->where('table_name', 'project_project_status')
                ->where('index_name', 'pps_unique')
                ->exists();

            if (!$indexExists) {
                Schema::table('project_project_status', function (Blueprint $table) {
                    $table->unique(['project_id', 'project_status_id', 'assigned_at'], 'pps_unique');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_project_status')) {
            Schema::drop('project_project_status');
        }
    }
};
