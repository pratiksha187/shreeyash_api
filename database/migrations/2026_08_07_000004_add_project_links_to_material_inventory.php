<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['material_requests', 'material_issues', 'stock_movements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('labour_site_id')->constrained('projects')->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'project_task_id')) {
                    $table->foreignId('project_task_id')->nullable()->after('project_id')->constrained('project_tasks')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['stock_movements', 'material_issues', 'material_requests'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'project_task_id')) {
                    $table->dropConstrainedForeignId('project_task_id');
                }

                if (Schema::hasColumn($tableName, 'project_id')) {
                    $table->dropConstrainedForeignId('project_id');
                }
            });
        }
    }
};
