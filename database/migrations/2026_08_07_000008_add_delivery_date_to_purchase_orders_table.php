<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_orders') || Schema::hasColumn('purchase_orders', 'delivery_date')) {
            return;
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('po_date');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_orders') || ! Schema::hasColumn('purchase_orders', 'delivery_date')) {
            return;
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
        });
    }
};
