<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FddRoadSection extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
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
