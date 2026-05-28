<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('daily_diesel_purchases')) {
            return;
        }

        Schema::create('daily_diesel_purchases', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date')->unique();
            $table->string('challan_no', 100)->nullable();
            $table->string('campar', 100)->nullable();
            $table->decimal('diesel_ltr', 10, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('khanav_opening_balance', 10, 2)->nullable();
            $table->decimal('khanav_today_supply', 10, 2)->default(0);
            $table->decimal('khanav_used', 10, 2)->default(0);
            $table->decimal('khalapur_opening_balance', 10, 2)->nullable();
            $table->decimal('khalapur_today_supply', 10, 2)->default(0);
            $table->decimal('khalapur_used', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_diesel_purchases');
    }
};
