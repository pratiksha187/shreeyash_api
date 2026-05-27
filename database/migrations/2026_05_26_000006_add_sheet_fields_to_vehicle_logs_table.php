<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_logs')) {
            return;
        }

        Schema::table('vehicle_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_logs', 'challan_no')) {
                $table->string('challan_no', 100)->nullable()->after('driver_mobile');
            }

            if (! Schema::hasColumn('vehicle_logs', 'diesel_added')) {
                $table->decimal('diesel_added', 10, 2)->default(0)->after('challan_no');
            }

            if (! Schema::hasColumn('vehicle_logs', 'start_reading')) {
                $table->decimal('start_reading', 12, 2)->default(0)->after('diesel_added');
            }

            if (! Schema::hasColumn('vehicle_logs', 'end_reading')) {
                $table->decimal('end_reading', 12, 2)->default(0)->after('start_reading');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicle_logs')) {
            return;
        }

        Schema::table('vehicle_logs', function (Blueprint $table) {
            foreach (['challan_no', 'diesel_added', 'start_reading', 'end_reading'] as $column) {
                if (Schema::hasColumn('vehicle_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
