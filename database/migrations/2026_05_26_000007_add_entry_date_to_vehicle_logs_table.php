<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_logs')) {
            return;
        }

        if (! Schema::hasColumn('vehicle_logs', 'entry_date')) {
            Schema::table('vehicle_logs', function (Blueprint $table) {
                $table->date('entry_date')->nullable()->after('id');
            });
        }

        DB::table('vehicle_logs')
            ->whereNull('entry_date')
            ->update([
                'entry_date' => DB::raw('DATE(COALESCE(out_at, in_at))'),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicle_logs') || ! Schema::hasColumn('vehicle_logs', 'entry_date')) {
            return;
        }

        Schema::table('vehicle_logs', function (Blueprint $table) {
            $table->dropColumn('entry_date');
        });
    }
};
