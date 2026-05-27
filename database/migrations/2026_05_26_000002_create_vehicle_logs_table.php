<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_logs')) {
            return;
        }

        Schema::create('vehicle_logs', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date')->nullable();
            $table->string('vehicle_number', 50);
            $table->string('vehicle_type', 100)->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_mobile', 20)->nullable();
            $table->timestamp('in_at');
            $table->timestamp('out_at')->nullable();
            $table->string('purpose', 500)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();

            $table->index('vehicle_number');
            $table->index('in_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_logs');
    }
};
