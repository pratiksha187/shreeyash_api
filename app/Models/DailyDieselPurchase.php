<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyDieselPurchase extends Model
{
    protected $fillable = [
        'entry_date',
        'challan_no',
        'campar',
        'diesel_ltr',
        'rate',
        'khanav_opening_balance',
        'khanav_today_supply',
        'khanav_used',
        'khalapur_opening_balance',
        'khalapur_today_supply',
        'khalapur_used',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'diesel_ltr' => 'decimal:2',
            'rate' => 'decimal:2',
            'khanav_opening_balance' => 'decimal:2',
            'khanav_today_supply' => 'decimal:2',
            'khanav_used' => 'decimal:2',
            'khalapur_opening_balance' => 'decimal:2',
            'khalapur_today_supply' => 'decimal:2',
            'khalapur_used' => 'decimal:2',
        ];
    }
}
