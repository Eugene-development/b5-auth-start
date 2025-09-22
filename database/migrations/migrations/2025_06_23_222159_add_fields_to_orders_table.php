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
            // Проценты от поставщика
            $table->decimal('supplier_percentage', 5, 2)->nullable()->comment('Проценты от поставщика');

            // Проценты для агента
            $table->decimal('agent_percentage', 5, 2)->nullable()->comment('Проценты для агента');

            // Email (без уникального индекса)
            $table->string('email')->nullable()->comment('Email клиента');

            // Планируемая дата выполнения
            $table->date('planned_completion_date')->nullable()->comment('Планируемая дата выполнения');

            // Фактическая дата выполнения
            $table->date('actual_completion_date')->nullable()->comment('Фактическая дата выполнения');

            // Дата договора
            $table->date('contract_date')->nullable()->comment('Дата договора');

            // Стоимость договора
            $table->decimal('contract_amount', 15, 2)->nullable()->comment('Стоимость договора');

            // Наименование договора
            $table->string('contract_name')->nullable()->comment('Наименование договора');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_percentage',
                'agent_percentage',
                'email',
                'planned_completion_date',
                'actual_completion_date',
                'contract_date',
                'contract_amount',
                'contract_name'
            ]);
        });
    }
};
