<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_date',
        'to_date',
        'present_days',
        'present_days_in_month',
        'weekoff_count',
        'holiday_count',
        'c_off_count',
        'leave_cl',
        'leave_sl',
        'leave_el',
        'leave_total',
        'half_day_count',
        'gross_salary',
        'per_day_rate',
        'basic_60',
        'hra_5',
        'conveyance_20',
        'other_allowance',
        'gross_payable',
        'pf_12',
        'insurance',
        'pt',
        'advance',
        'total_deduction',
        'net_payable',
        'pdf_file_path',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'present_days' => 'decimal:2',
            'present_days_in_month' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'per_day_rate' => 'decimal:2',
            'basic_60' => 'decimal:2',
            'hra_5' => 'decimal:2',
            'conveyance_20' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'gross_payable' => 'decimal:2',
            'pf_12' => 'decimal:2',
            'insurance' => 'decimal:2',
            'pt' => 'decimal:2',
            'advance' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_payable' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
