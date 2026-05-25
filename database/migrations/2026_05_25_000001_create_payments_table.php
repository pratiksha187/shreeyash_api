<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('present_days', 8, 2)->default(0);
            $table->decimal('present_days_in_month', 8, 2)->default(0);
            $table->unsignedInteger('weekoff_count')->default(0);
            $table->unsignedInteger('holiday_count')->default(0);
            $table->unsignedInteger('c_off_count')->default(0);
            $table->unsignedInteger('leave_cl')->default(0);
            $table->unsignedInteger('leave_sl')->default(0);
            $table->unsignedInteger('leave_el')->default(0);
            $table->unsignedInteger('leave_total')->default(0);
            $table->unsignedInteger('half_day_count')->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('per_day_rate', 12, 2)->default(0);
            $table->decimal('basic_60', 12, 2)->default(0);
            $table->decimal('hra_5', 12, 2)->default(0);
            $table->decimal('conveyance_20', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('gross_payable', 12, 2)->default(0);
            $table->decimal('pf_12', 12, 2)->default(0);
            $table->decimal('insurance', 12, 2)->default(0);
            $table->decimal('pt', 12, 2)->default(0);
            $table->decimal('advance', 12, 2)->default(0);
            $table->decimal('total_deduction', 12, 2)->default(0);
            $table->decimal('net_payable', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'from_date', 'to_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
