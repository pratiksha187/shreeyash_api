<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourCostingRecord extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'labour_id',
        'contractor_id',
        'labour_site_id',
        'work_date',
        'labour_type',
        'shift',
        'work_category',
        'payable_days',
        'work_hours',
        'overtime_hours',
        'daily_wage_rate',
        'overtime_rate',
        'base_amount',
        'overtime_amount',
        'total_amount',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'payable_days' => 'decimal:2',
            'work_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'daily_wage_rate' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(Labour::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'labour_site_id');
    }
}
