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
        Schema::create('order_order_status', function (Blueprint $table) {
            // Первичный ключ
            $table->ulid('id')->primary();

            // Внешний ключ на заказ
            $table->ulid('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            // Внешний ключ на статус заказа
            $table->ulid('order_status_id');
            $table->foreign('order_status_id')->references('id')->on('order_statuses')->onDelete('cascade');

            // Время назначения статуса
            $table->timestamp('assigned_at')->useCurrent();

            // Кто назначил статус (опционально, может быть ID пользователя)
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

            // Комментарий к назначению статуса
            $table->text('comment')->nullable();

            // Уникальный индекс для предотвращения дублирования
            $table->unique(['order_id', 'order_status_id', 'assigned_at']);

            // Временные метки
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_order_status');
    }
};
