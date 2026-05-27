<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'fixed_monthly_amount')) {
                $table->decimal('fixed_monthly_amount', 12, 2)->default(0)->after('driver_mobile');
            }

            if (! Schema::hasColumn('vehicles', 'ot_rate')) {
                $table->decimal('ot_rate', 10, 2)->default(0)->after('fixed_monthly_amount');
            }

            if (! Schema::hasColumn('vehicles', 'tds_percentage')) {
                $table->decimal('tds_percentage', 5, 2)->default(1)->after('ot_rate');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            foreach (['fixed_monthly_amount', 'ot_rate', 'tds_percentage'] as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
