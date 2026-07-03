<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDriverAttendance extends Model
{
    use BelongsToCompany, HasFactory;

    public const STATUSES = [
        'present',
        'absent',
        'half_day',
        'leave',
    ];

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'vehicle_driver_id',
        'attendance_date',
        'status',
        'in_time',
        'out_time',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(VehicleDriver::class, 'vehicle_driver_id');
    }
}
