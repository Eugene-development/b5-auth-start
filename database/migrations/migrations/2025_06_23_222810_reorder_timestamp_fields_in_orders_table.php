<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Удаляем существующие временные поля
            $table->dropTimestamps();
            $table->dropSoftDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            // Добавляем временные поля в конец таблицы
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // В случае отката оставляем поля как есть
        // Порядок полей в rollback не критичен
    }
};
