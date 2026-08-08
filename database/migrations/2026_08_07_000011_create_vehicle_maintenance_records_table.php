<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_maintenance_records')) {
            return;
        }

        Schema::create('vehicle_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('record_type', 40)->default('service');
            $table->string('job_card_no', 100)->nullable();
            $table->date('record_date');
            $table->date('next_service_date')->nullable();
            $table->decimal('meter_reading', 15, 2)->default(0);
            $table->decimal('idle_hours', 10, 2)->default(0);
            $table->decimal('breakdown_hours', 10, 2)->default(0);
            $table->decimal('service_cost', 15, 2)->default(0);
            $table->decimal('repair_cost', 15, 2)->default(0);
            $table->decimal('fuel_cost', 15, 2)->default(0);
            $table->decimal('depreciation_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('working_hours', 10, 2)->default(0);
            $table->decimal('cost_per_hour', 15, 2)->default(0);
            $table->string('status', 40)->default('open');
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'record_date']);
            $table->index(['company_id', 'record_type']);
            $table->index(['vehicle_id', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_records');
    }
};
