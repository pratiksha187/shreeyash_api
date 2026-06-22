<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->shiftAttendanceTimes('+5 hours 30 minutes', 'DATE_ADD(%s, INTERVAL 330 MINUTE)');
    }

    public function down(): void
    {
        $this->shiftAttendanceTimes('-5 hours 30 minutes', 'DATE_SUB(%s, INTERVAL 330 MINUTE)');
    }

    private function shiftAttendanceTimes(string $sqliteModifier, string $mysqlExpression): void
    {
        foreach (['check_in_at', 'check_out_at'] as $column) {
            $expression = DB::getDriverName() === 'sqlite'
                ? "datetime({$column}, '{$sqliteModifier}')"
                : sprintf($mysqlExpression, $column);

            DB::table('attendances')
                ->whereNotNull($column)
                ->update([$column => DB::raw($expression)]);
        }
    }
};
