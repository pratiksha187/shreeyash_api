<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyStock extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'safety_item_id',
        'labour_site_id',
        'available_quantity',
    ];

    protected function casts(): array
    {
        return [
            'available_quantity' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SafetyItem::class, 'safety_item_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'labour_site_id');
    }
}
