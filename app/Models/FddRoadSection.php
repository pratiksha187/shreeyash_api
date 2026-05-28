<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FddRoadSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_number',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'group_number' => 'integer',
        ];
    }

    public function testRecords(): HasMany
    {
        return $this->hasMany(FddTestRecord::class);
    }
}
