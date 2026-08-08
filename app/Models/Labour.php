<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Labour extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'contractor_id',
        'name',
        'mobile',
        'labour_code',
        'trade',
        'labour_type',
        'work_category',
        'daily_wage_rate',
        'overtime_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'daily_wage_rate' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
        ];
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function labourAttendances(): HasMany
    {
        return $this->hasMany(LabourAttendance::class);
    }

    public function costingRecords(): HasMany
    {
        return $this->hasMany(LabourCostingRecord::class);
    }
}
