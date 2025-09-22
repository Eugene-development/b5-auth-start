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
        Schema::create('orders', function (Blueprint $table) {
            // 1. Первичный ключ с ULID
            $table->ulid('id')->primary();

            // 2. Уникальный ключ проекта в формате ULID
            $table->ulid('key')->unique();

            // 3. Поле активности
            $table->boolean('is_active')->default(true);

            // 4. Изделие
            $table->string('product');

            // 5. Комментарий
            $table->text('comment')->nullable();

            // 6-7. Поля для полиморфного отношения
            $table->ulidMorphs('parentable');

            // 8-9-10. Временные метки создания, изменения и мягкого удаления
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
