<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->foreignId('parent_task_id')->nullable()->after('project_id')->constrained('project_tasks')->nullOnDelete();
            $table->string('structure_type', 30)->default('task')->after('parent_task_id');
            $table->unsignedInteger('sort_order')->default(0)->after('structure_type');

            $table->index(['project_id', 'parent_task_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'parent_task_id', 'sort_order']);
            $table->dropConstrainedForeignId('parent_task_id');
            $table->dropColumn(['structure_type', 'sort_order']);
        });
    }
};
