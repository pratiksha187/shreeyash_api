<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineryDieselLog extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'engineer_user_id',
        'labour_site_id',
        'issue_date',
        'machinery',
        'minimum_stock_ltr',
        'daily_diesel_for_8hr_ltr',
        'yesterday_balance_ltr',
        'diesel_to_issue_today_ltr',
        'actual_diesel_issued_today_ltr',
        'extra_diesel_issued_ltr',
        'total_diesel_available_after_filling_ltr',
        'hours_worked',
        'expected_consumption_ltr',
        'expected_closing_balance_ltr',
        'evening_physical_balance_ltr',
        'difference_ltr',
        'diesel_to_issue_tomorrow_ltr',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'minimum_stock_ltr' => 'decimal:2',
            'daily_diesel_for_8hr_ltr' => 'decimal:2',
            'yesterday_balance_ltr' => 'decimal:2',
            'diesel_to_issue_today_ltr' => 'decimal:2',
            'actual_diesel_issued_today_ltr' => 'decimal:2',
            'extra_diesel_issued_ltr' => 'decimal:2',
            'total_diesel_available_after_filling_ltr' => 'decimal:2',
            'hours_worked' => 'decimal:2',
            'expected_consumption_ltr' => 'decimal:2',
            'expected_closing_balance_ltr' => 'decimal:2',
            'evening_physical_balance_ltr' => 'decimal:2',
            'difference_ltr' => 'decimal:2',
            'diesel_to_issue_tomorrow_ltr' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (MachineryDieselLog $log) {
            $log->calculateDieselFields();
        });
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_user_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'labour_site_id');
    }

    public function calculateDieselFields(): void
    {
        $minimumStock = $this->number($this->minimum_stock_ltr);
        $dailyDiesel = $this->number($this->daily_diesel_for_8hr_ltr);
        $yesterdayBalance = $this->number($this->yesterday_balance_ltr);
        $actualIssued = $this->number($this->actual_diesel_issued_today_ltr);
        $hoursWorked = $this->number($this->hours_worked);
        $eveningPhysicalBalance = $this->nullableNumber($this->evening_physical_balance_ltr);

        $issueToday = max(0, $minimumStock + $dailyDiesel - $yesterdayBalance);
        $expectedConsumption = $dailyDiesel > 0
            ? ($dailyDiesel / 8) * $hoursWorked
            : 0;
        $availableAfterFilling = $yesterdayBalance + $actualIssued;
        $expectedClosingBalance = $availableAfterFilling - $expectedConsumption;
        $balanceForTomorrow = $eveningPhysicalBalance ?? $expectedClosingBalance;

        $this->diesel_to_issue_today_ltr = round($issueToday, 2);
        $this->extra_diesel_issued_ltr = round($actualIssued - $issueToday, 2);
        $this->total_diesel_available_after_filling_ltr = round($availableAfterFilling, 2);
        $this->expected_consumption_ltr = round($expectedConsumption, 2);
        $this->expected_closing_balance_ltr = round($expectedClosingBalance, 2);
        $this->difference_ltr = $eveningPhysicalBalance === null
            ? null
            : round($eveningPhysicalBalance - $expectedClosingBalance, 2);
        $this->diesel_to_issue_tomorrow_ltr = round(max(0, $minimumStock + $dailyDiesel - $balanceForTomorrow), 2);

        if (! filled($this->remarks)) {
            $this->remarks = $this->autoRemarks();
        }
    }

    public function autoRemarks(): ?string
    {
        if ($this->difference_ltr === null) {
            return null;
        }

        $difference = round((float) $this->difference_ltr, 2);

        if ($difference > 0) {
            return 'Extra Diesel Remaining';
        }

        if ($difference < 0) {
            return 'Diesel Missing';
        }

        return 'Diesel Balance OK';
    }

    private function number(mixed $value): float
    {
        return (float) ($value ?? 0);
    }

    private function nullableNumber(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}
