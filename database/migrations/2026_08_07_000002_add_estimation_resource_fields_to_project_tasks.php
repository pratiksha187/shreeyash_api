<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('project_tasks', 'material_template')) {
                $table->text('material_template')->nullable()->after('quantity_unit');
            }

            if (! Schema::hasColumn('project_tasks', 'planned_material_qty')) {
                $table->decimal('planned_material_qty', 12, 3)->default(0)->after('material_template');
            }

            if (! Schema::hasColumn('project_tasks', 'planned_labour_count')) {
                $table->unsignedInteger('planned_labour_count')->default(0)->after('planned_material_qty');
            }

            if (! Schema::hasColumn('project_tasks', 'planned_machinery_count')) {
                $table->unsignedInteger('planned_machinery_count')->default(0)->after('planned_labour_count');
            }

            if (! Schema::hasColumn('project_tasks', 'variance_limit_percent')) {
                $table->decimal('variance_limit_percent', 5, 2)->default(10)->after('planned_machinery_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('project_tasks', 'material_template') ? 'material_template' : null,
                Schema::hasColumn('project_tasks', 'planned_material_qty') ? 'planned_material_qty' : null,
                Schema::hasColumn('project_tasks', 'planned_labour_count') ? 'planned_labour_count' : null,
                Schema::hasColumn('project_tasks', 'planned_machinery_count') ? 'planned_machinery_count' : null,
                Schema::hasColumn('project_tasks', 'variance_limit_percent') ? 'variance_limit_percent' : null,
            ]);

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
