<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('challan_no', 50)->unique();
            $table->date('challan_date');
            $table->string('party_name');
            $table->string('material_machine');
            $table->string('vehicle_no', 100);
            $table->string('measurement', 100);
            $table->string('location');
            $table->string('delivery_time', 100);
            $table->string('receiver_name', 150);
            $table->string('driver_name', 150);
            $table->timestamps();

            $table->foreign('user_id', 'challans_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index('challan_date', 'challans_date_index');
            $table->index('party_name', 'challans_party_index');
            $table->index('vehicle_no', 'challans_vehicle_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challans');
    }
};
