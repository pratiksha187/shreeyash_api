<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 40)->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->text('remark')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'project_task_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_updates');
    }
};
