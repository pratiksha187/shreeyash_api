<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'entry_date',
        'vehicle_number',
        'vehicle_type',
        'driver_name',
        'driver_mobile',
        'challan_no',
        'diesel_added',
        'start_reading',
        'end_reading',
        'in_at',
        'out_at',
        'purpose',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'in_at' => 'datetime',
            'out_at' => 'datetime',
            'diesel_added' => 'decimal:2',
            'start_reading' => 'decimal:2',
            'end_reading' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
