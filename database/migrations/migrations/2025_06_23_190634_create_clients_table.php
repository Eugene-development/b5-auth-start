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
        Schema::create('clients', function (Blueprint $table) {
            // 1. Первичный ключ с ULID
            $table->ulid('id')->primary();

            // 2. Уникальный ключ проекта в формате ULID
            $table->ulid('key')->unique();

            // 3. Поле активности
            $table->boolean('is_active')->default(true);

            // 4. Имя клиента
            $table->string('name');

            // 5. Телефон клиента
            $table->string('phone')->nullable();

            // 6. Адрес клиента
            $table->text('address')->nullable();

            // 8. Комментарий
            $table->text('comment')->nullable();

            // 9-10. Поля для полиморфного отношения
            $table->ulidMorphs('parentable');

            // 11-12-13. Временные метки создания, изменения и мягкого удаления
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
