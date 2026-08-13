<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_purchases') || Schema::hasColumn('product_purchases', 'bill_photo_path')) {
            return;
        }

        Schema::table('product_purchases', function (Blueprint $table) {
            $table->string('bill_photo_path')->nullable()->after('total_amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_purchases') || ! Schema::hasColumn('product_purchases', 'bill_photo_path')) {
            return;
        }

        Schema::table('product_purchases', function (Blueprint $table) {
            $table->dropColumn('bill_photo_path');
        });
    }
};
