<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyDieselPurchaseSiteEntry extends Model
{
    protected $fillable = [
        'daily_diesel_purchase_id',
        'labour_site_id',
        'opening_balance',
        'today_supply',
        'used',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'today_supply' => 'decimal:2',
            'used' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(DailyDieselPurchase::class, 'daily_diesel_purchase_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'labour_site_id');
    }
}
