<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMaintenanceRecord extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'record_type',
        'job_card_no',
        'record_date',
        'next_service_date',
        'meter_reading',
        'idle_hours',
        'breakdown_hours',
        'service_cost',
        'repair_cost',
        'fuel_cost',
        'depreciation_cost',
        'total_cost',
        'working_hours',
        'cost_per_hour',
        'status',
        'description',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'next_service_date' => 'date',
            'meter_reading' => 'decimal:2',
            'idle_hours' => 'decimal:2',
            'breakdown_hours' => 'decimal:2',
            'service_cost' => 'decimal:2',
            'repair_cost' => 'decimal:2',
            'fuel_cost' => 'decimal:2',
            'depreciation_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'working_hours' => 'decimal:2',
            'cost_per_hour' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
