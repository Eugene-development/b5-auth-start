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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('value')->after('name');
        });

        DB::statement('UPDATE projects SET value = name');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->boolean('is_active')->default(true)->after('description');
            $table->string('contract_name')->nullable()->comment('Номер договора')->after('is_active');
            $table->date('contract_date')->nullable()->comment('Дата договора')->after('contract_name');
            $table->decimal('contract_amount', 12, 2)->nullable()->comment('Стоимость договора')->after('contract_date');
            $table->decimal('agent_percentage', 5, 2)->nullable()->comment('Проценты агенту')->after('contract_amount');
            $table->date('planned_completion_date')->nullable()->after('agent_percentage');

            $table->dropColumn(['status', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('name')->after('value');
        });

        DB::statement('UPDATE projects SET name = value');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'value',
                'is_active',
                'contract_name',
                'contract_date',
                'contract_amount',
                'agent_percentage',
                'planned_completion_date',
            ]);

            $table->enum('status', ['active', 'completed', 'paused'])->default('active');
            $table->string('phone')->nullable();
        });
    }
};
