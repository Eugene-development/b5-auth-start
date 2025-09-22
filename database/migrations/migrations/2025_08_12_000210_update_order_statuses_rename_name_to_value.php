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
        Schema::table('order_statuses', function (Blueprint $table) {
            $table->string('value')->after('name');
        });

        DB::statement('UPDATE order_statuses SET value = name');

        Schema::table('order_statuses', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_statuses', function (Blueprint $table) {
            $table->string('name')->after('value');
        });

        DB::statement('UPDATE order_statuses SET name = value');

        Schema::table('order_statuses', function (Blueprint $table) {
            $table->dropColumn('value');
        });
    }
};
