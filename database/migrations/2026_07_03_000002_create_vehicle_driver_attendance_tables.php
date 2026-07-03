<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_drivers')) {
            Schema::create('vehicle_drivers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('mobile', 20)->nullable();
                $table->string('license_number', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('remarks', 500)->nullable();
                $table->timestamps();

                $table->index(['company_id', 'vehicle_id']);
                $table->index(['company_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('vehicle_driver_attendances')) {
            Schema::create('vehicle_driver_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_driver_id')->constrained('vehicle_drivers')->cascadeOnDelete();
                $table->date('attendance_date');
                $table->string('status', 20)->default('present');
                $table->time('in_time')->nullable();
                $table->time('out_time')->nullable();
                $table->string('remarks', 1000)->nullable();
                $table->timestamps();

                $table->unique(['vehicle_driver_id', 'attendance_date'], 'vehicle_driver_att_date_unique');
                $table->index(['company_id', 'attendance_date'], 'vehicle_driver_att_company_date_index');
                $table->index(['vehicle_id', 'attendance_date'], 'vehicle_driver_att_vehicle_date_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_driver_attendances');
        Schema::dropIfExists('vehicle_drivers');
    }
};
