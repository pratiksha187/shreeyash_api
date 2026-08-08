<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_boq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('boq_no', 80)->nullable();
            $table->string('parent_boq_no', 80)->nullable();
            $table->string('item_type', 20)->default('item');
            $table->string('group_name')->nullable();
            $table->text('task_name');
            $table->string('unit', 40)->nullable();
            $table->decimal('rate', 14, 2)->default(0);
            $table->decimal('tender_qty', 14, 3)->default(0);
            $table->decimal('scope_qty', 14, 3)->default(0);
            $table->decimal('subcontractor_done_qty', 14, 3)->default(0);
            $table->decimal('self_done_qty', 14, 3)->default(0);
            $table->decimal('done_qty', 14, 3)->default(0);
            $table->decimal('balance_qty', 14, 3)->default(0);
            $table->decimal('balance_estimate', 14, 2)->default(0);
            $table->decimal('billed_amount', 14, 2)->default(0);
            $table->decimal('dpr_unbilled_amount', 14, 2)->default(0);
            $table->decimal('progress_percent', 8, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'project_id']);
            $table->index(['project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_boq_items');
    }
};
