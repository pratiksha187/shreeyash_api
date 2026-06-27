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
            if (! Schema::hasColumn('product_purchases', 'material_id')) {
                $table->foreignId('material_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('product_purchases', 'stock_labour_site_id')) {
                $table->foreignId('stock_labour_site_id')->nullable()->after('material_id')->constrained('labour_sites')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_purchases')) {
            return;
        }

        Schema::table('product_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('product_purchases', 'stock_labour_site_id')) {
                $table->dropConstrainedForeignId('stock_labour_site_id');
            }

            if (Schema::hasColumn('product_purchases', 'material_id')) {
                $table->dropConstrainedForeignId('material_id');
            }
        });
    }
};
