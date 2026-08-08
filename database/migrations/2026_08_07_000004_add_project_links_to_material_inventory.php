<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['material_requests', 'material_issues', 'stock_movements'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'project_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('project_id')->nullable()->after('labour_site_id');
                });
            }

            if (! Schema::hasColumn($tableName, 'project_task_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('project_task_id')->nullable()->after('project_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['stock_movements', 'material_issues', 'material_requests'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'project_task_id')) {
                    $table->dropColumn('project_task_id');
                }

                if (Schema::hasColumn($tableName, 'project_id')) {
                    $table->dropColumn('project_id');
                }
            });
        }
    }
};
