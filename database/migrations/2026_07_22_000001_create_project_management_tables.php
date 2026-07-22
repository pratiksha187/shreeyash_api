<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('planning_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('client_name')->nullable();
            $table->string('site_location')->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->decimal('budget_amount', 12, 2)->default(0);
            $table->string('status', 40)->default('planned');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'target_date']);
        });

        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('work_area')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->string('status', 40)->default('pending');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->text('description')->nullable();
            $table->text('completion_note')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'due_date']);
            $table->index(['assigned_engineer_id', 'status']);
            $table->index(['assigned_supervisor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('projects');
    }
};
