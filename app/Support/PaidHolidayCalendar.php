<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class PaidHolidayCalendar
{
    public static function holidaysBetween(Carbon $from, Carbon $to): Collection
    {
        $holidays = collect();
        $configuredHolidays = config('admin.paid_holidays', []);

        foreach (CarbonPeriod::create($from->copy()->startOfYear(), '1 year', $to->copy()->startOfYear()) as $yearDate) {
            foreach ($configuredHolidays[$yearDate->year] ?? [] as $date => $name) {
                $holidayDate = Carbon::parse($date)->startOfDay();

                if ($holidayDate->betweenIncluded($from->copy()->startOfDay(), $to->copy()->startOfDay())) {
                    $holidays->put($holidayDate->toDateString(), [
                        'date' => $holidayDate,
                        'name' => $name,
                    ]);
                }
            }
        }

        return $holidays->sortKeys();
    }
}
