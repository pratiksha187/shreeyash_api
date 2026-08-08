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
            if (! Schema::hasColumn('product_purchases', 'safety_item_id')) {
                $table->unsignedBigInteger('safety_item_id')->nullable()->after('material_id');
                $table->index('safety_item_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_purchases')) {
            return;
        }

        Schema::table('product_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('product_purchases', 'safety_item_id')) {
                $table->dropIndex(['safety_item_id']);
                $table->dropColumn('safety_item_id');
            }
        });
    }
};
