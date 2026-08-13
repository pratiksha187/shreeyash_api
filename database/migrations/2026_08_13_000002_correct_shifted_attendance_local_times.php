<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        $this->convertAttendanceTimesToDateTime();
        $this->shiftLikelyOffsetRows('check_in_at', [
            ['>=', '13:00:00'],
        ]);
        $this->shiftLikelyOffsetRows('check_out_at', [
            ['>=', '22:00:00'],
            ['<=', '03:00:00'],
        ]);
    }

    public function down(): void
    {
        //
    }

    private function convertAttendanceTimesToDateTime(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (['check_in_at', 'check_out_at', 'leave_approved_at', 'logout_reminder_sent_at'] as $column) {
            if (Schema::hasColumn('attendances', $column)) {
                DB::statement("ALTER TABLE `attendances` MODIFY `{$column}` DATETIME NULL");
            }
        }
    }

    private function shiftLikelyOffsetRows(string $column, array $timeConditions): void
    {
        if (! Schema::hasColumn('attendances', $column)) {
            return;
        }

        $driver = DB::getDriverName();

        $query = DB::table('attendances')
            ->whereNotNull($column)
            ->where(function ($query) use ($column, $driver, $timeConditions) {
                foreach ($timeConditions as [$operator, $time]) {
                    if ($driver === 'sqlite') {
                        $query->orWhereRaw("time({$column}) {$operator} time(?)", [$time]);
                    } else {
                        $query->orWhereRaw("TIME(`{$column}`) {$operator} ?", [$time]);
                    }
                }
            });

        if ($driver === 'sqlite') {
            $expression = "datetime({$column}, '-330 minutes')";
        } else {
            $expression = "DATE_SUB(`{$column}`, INTERVAL 330 MINUTE)";
        }

        $query->update([$column => DB::raw($expression)]);
    }
};
