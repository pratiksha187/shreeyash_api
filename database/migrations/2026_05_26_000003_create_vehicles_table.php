<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            return;
        }

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_number', 50)->unique();
            $table->string('vehicle_type', 100)->nullable();
            $table->string('owner_name')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_mobile', 20)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
