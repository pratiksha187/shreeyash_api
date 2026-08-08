<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contractor extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'labour_site_id',
        'name',
        'mobile',
        'agreement_no',
        'contract_no',
        'work_order_no',
        'contract_start_date',
        'contract_end_date',
        'contract_value',
        'progress_percent',
        'last_measurement_date',
        'last_measurement_summary',
        'last_ra_bill_no',
        'last_ra_bill_amount',
        'retention_percent',
        'recovery_amount',
        'tds_percent',
        'gst_percent',
        'net_payable_amount',
        'renewal_due_date',
        'remarks',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'contract_value' => 'decimal:2',
            'progress_percent' => 'decimal:2',
            'last_measurement_date' => 'date',
            'last_ra_bill_amount' => 'decimal:2',
            'retention_percent' => 'decimal:2',
            'recovery_amount' => 'decimal:2',
            'tds_percent' => 'decimal:2',
            'gst_percent' => 'decimal:2',
            'net_payable_amount' => 'decimal:2',
            'renewal_due_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'labour_site_id');
    }

    public function labours(): HasMany
    {
        return $this->hasMany(Labour::class);
    }

    public function labourAttendances(): HasMany
    {
        return $this->hasMany(LabourAttendance::class);
    }
}
