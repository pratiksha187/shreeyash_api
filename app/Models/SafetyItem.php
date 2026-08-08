<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'category',
        'unit',
        'minimum_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(SafetyStock::class);
    }
}
