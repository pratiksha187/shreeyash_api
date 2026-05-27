<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_logs')) {
            return;
        }

        $logs = DB::table('vehicle_logs')
            ->whereNotNull('out_at')
            ->whereNotNull('remarks')
            ->select('id', 'entry_date', 'in_at', 'out_at', 'remarks')
            ->get();

        foreach ($logs as $log) {
            $outAt = Carbon::parse($log->out_at);
            $inAt = Carbon::parse($log->in_at);

            if ($inAt->isSameDay($outAt) || ! preg_match('/Total Hrs:\s*(\d{1,3}):(\d{2})/i', $log->remarks, $matches)) {
                continue;
            }

            $minutes = ((int) $matches[1] * 60) + (int) $matches[2];

            DB::table('vehicle_logs')
                ->where('id', $log->id)
                ->update([
                    'entry_date' => $outAt->toDateString(),
                    'in_at' => $outAt->copy()->subMinutes($minutes),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        //
    }
};
