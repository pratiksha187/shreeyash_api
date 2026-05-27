<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_logs') || ! Schema::hasTable('vehicles')) {
            return;
        }

        if (! Schema::hasColumn('vehicle_logs', 'vehicle_id')) {
            Schema::table('vehicle_logs', function (Blueprint $table) {
                $table->foreignId('vehicle_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('vehicles')
                    ->nullOnDelete();
            });
        }

        $logs = DB::table('vehicle_logs')
            ->whereNull('vehicle_id')
            ->whereNotNull('vehicle_number')
            ->select('vehicle_number', 'vehicle_type', 'driver_name', 'driver_mobile')
            ->get()
            ->groupBy('vehicle_number');

        foreach ($logs as $vehicleNumber => $vehicleLogs) {
            if (! $vehicleNumber) {
                continue;
            }

            $firstLog = $vehicleLogs->first();
            $vehicleId = DB::table('vehicles')->where('vehicle_number', $vehicleNumber)->value('id');

            if (! $vehicleId) {
                $vehicleId = DB::table('vehicles')->insertGetId([
                    'vehicle_number' => $vehicleNumber,
                    'vehicle_type' => $firstLog->vehicle_type,
                    'driver_name' => $firstLog->driver_name,
                    'driver_mobile' => $firstLog->driver_mobile,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('vehicle_logs')
                ->where('vehicle_number', $vehicleNumber)
                ->whereNull('vehicle_id')
                ->update([
                    'vehicle_id' => $vehicleId,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicle_logs') || ! Schema::hasColumn('vehicle_logs', 'vehicle_id')) {
            return;
        }

        Schema::table('vehicle_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
        });
    }
};
