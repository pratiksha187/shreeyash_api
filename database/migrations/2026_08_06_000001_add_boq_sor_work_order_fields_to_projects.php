<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('work_order_number')->nullable()->after('site_location');
            $table->date('work_order_date')->nullable()->after('work_order_number');
            $table->string('boq_reference')->nullable()->after('work_order_date');
            $table->string('sor_reference')->nullable()->after('boq_reference');
            $table->string('quantity_unit', 40)->nullable()->after('budget_amount');
            $table->decimal('planned_quantity', 14, 3)->default(0)->after('quantity_unit');
            $table->decimal('executed_quantity', 14, 3)->default(0)->after('planned_quantity');
            $table->decimal('estimated_cost', 14, 2)->default(0)->after('executed_quantity');
            $table->decimal('actual_cost', 14, 2)->default(0)->after('estimated_cost');
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->string('boq_item_number', 80)->nullable()->after('work_area');
            $table->string('sor_item_number', 80)->nullable()->after('boq_item_number');
            $table->string('quantity_unit', 40)->nullable()->after('estimated_hours');
            $table->decimal('planned_quantity', 14, 3)->default(0)->after('quantity_unit');
            $table->decimal('executed_quantity', 14, 3)->default(0)->after('planned_quantity');
            $table->decimal('rate', 14, 2)->default(0)->after('executed_quantity');
            $table->decimal('planned_cost', 14, 2)->default(0)->after('rate');
            $table->decimal('actual_cost', 14, 2)->default(0)->after('planned_cost');
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'boq_item_number',
                'sor_item_number',
                'quantity_unit',
                'planned_quantity',
                'executed_quantity',
                'rate',
                'planned_cost',
                'actual_cost',
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'work_order_number',
                'work_order_date',
                'boq_reference',
                'sor_reference',
                'quantity_unit',
                'planned_quantity',
                'executed_quantity',
                'estimated_cost',
                'actual_cost',
            ]);
        });
    }
};
