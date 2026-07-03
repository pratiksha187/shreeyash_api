<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleDriver extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'name',
        'mobile',
        'license_number',
        'is_active',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(VehicleDriverAttendance::class);
    }
}
