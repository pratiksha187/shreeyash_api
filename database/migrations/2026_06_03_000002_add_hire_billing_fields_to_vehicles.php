<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (! Schema::hasColumn('vehicles', 'default_site')) {
                    $table->string('default_site')->nullable()->after('driver_mobile');
                }

                if (! Schema::hasColumn('vehicles', 'hire_per_day_rate')) {
                    $table->decimal('hire_per_day_rate', 12, 2)->default(0)->after('ot_rate');
                }

                if (! Schema::hasColumn('vehicles', 'hire_per_hour_rate')) {
                    $table->decimal('hire_per_hour_rate', 12, 2)->default(0)->after('hire_per_day_rate');
                }

                if (! Schema::hasColumn('vehicles', 'gst_percentage')) {
                    $table->decimal('gst_percentage', 5, 2)->default(18)->after('tds_percentage');
                }

                if (! Schema::hasColumn('vehicles', 'extra_sunday_paid_amount')) {
                    $table->decimal('extra_sunday_paid_amount', 12, 2)->default(0)->after('gst_percentage');
                }
            });
        }

        if (Schema::hasTable('vehicle_logs')) {
            Schema::table('vehicle_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('vehicle_logs', 'site_name')) {
                    $table->string('site_name')->nullable()->after('challan_no');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicle_logs')) {
            Schema::table('vehicle_logs', function (Blueprint $table) {
                if (Schema::hasColumn('vehicle_logs', 'site_name')) {
                    $table->dropColumn('site_name');
                }
            });
        }

        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                foreach ([
                    'default_site',
                    'hire_per_day_rate',
                    'hire_per_hour_rate',
                    'gst_percentage',
                    'extra_sunday_paid_amount',
                ] as $column) {
                    if (Schema::hasColumn('vehicles', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
