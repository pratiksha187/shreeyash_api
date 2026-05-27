<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabourSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function contractors(): HasMany
    {
        return $this->hasMany(Contractor::class);
    }

    public function labourAttendances(): HasMany
    {
        return $this->hasMany(LabourAttendance::class);
    }
}
