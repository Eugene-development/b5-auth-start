<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Заполняем поле key для существующих пользователей
        User::whereNull('key')->orWhere('key', '')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $user->update(['key' => Str::ulid()]);
            }
        });

        // Добавляем уникальный индекс
        Schema::table('users', function (Blueprint $table) {
            $table->unique('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['key']);
        });
    }
};
