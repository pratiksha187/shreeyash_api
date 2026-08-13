<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_purchases')) {
            return;
        }

        Schema::table('product_purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('product_purchases', 'material_photo_path')) {
                $table->string('material_photo_path')->nullable()->after('total_amount');
            }

            if (! Schema::hasColumn('product_purchases', 'bill_photo_path')) {
                $table->string('bill_photo_path')->nullable()->after(
                    Schema::hasColumn('product_purchases', 'material_photo_path') ? 'material_photo_path' : 'total_amount'
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_purchases')) {
            return;
        }

        Schema::table('product_purchases', function (Blueprint $table) {
            foreach (['bill_photo_path', 'material_photo_path'] as $column) {
                if (Schema::hasColumn('product_purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
