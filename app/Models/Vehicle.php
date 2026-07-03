<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'vehicle_number',
        'vehicle_type',
        'owner_name',
        'driver_name',
        'driver_mobile',
        'default_site',
        'fixed_monthly_amount',
        'ot_rate',
        'hire_per_day_rate',
        'hire_per_hour_rate',
        'tds_percentage',
        'gst_percentage',
        'extra_sunday_paid_amount',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'fixed_monthly_amount' => 'decimal:2',
            'ot_rate' => 'decimal:2',
            'hire_per_day_rate' => 'decimal:2',
            'hire_per_hour_rate' => 'decimal:2',
            'tds_percentage' => 'decimal:2',
            'gst_percentage' => 'decimal:2',
            'extra_sunday_paid_amount' => 'decimal:2',
        ];
    }

    public function vehicleLogs(): HasMany
    {
        return $this->hasMany(VehicleLog::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(VehicleDriver::class);
    }

    public function driverAttendances(): HasMany
    {
        return $this->hasMany(VehicleDriverAttendance::class);
    }
}
