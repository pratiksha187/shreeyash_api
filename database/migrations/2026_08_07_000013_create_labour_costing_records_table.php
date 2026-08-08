<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('labour_costing_records')) {
            return;
        }

        Schema::create('labour_costing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('labour_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('labour_site_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->string('labour_type', 40)->default('daily_wage');
            $table->string('shift', 40)->default('day');
            $table->string('work_category', 120)->nullable();
            $table->decimal('payable_days', 8, 2)->default(1);
            $table->decimal('work_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('daily_wage_rate', 12, 2)->default(0);
            $table->decimal('overtime_rate', 12, 2)->default(0);
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 40)->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'work_date']);
            $table->index(['company_id', 'work_category']);
            $table->index(['labour_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_costing_records');
    }
};
