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
            if (! Schema::hasColumn('product_purchases', 'size')) {
                $table->string('size', 100)->nullable()->after('product_name');
            }

            if (! Schema::hasColumn('product_purchases', 'pcs')) {
                $table->decimal('pcs', 12, 2)->default(0)->after('size');
            }

            if (! Schema::hasColumn('product_purchases', 'weight_kg')) {
                $table->decimal('weight_kg', 12, 2)->default(0)->after('pcs');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_purchases')) {
            return;
        }

        Schema::table('product_purchases', function (Blueprint $table) {
            foreach (['weight_kg', 'pcs', 'size'] as $column) {
                if (Schema::hasColumn('product_purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
