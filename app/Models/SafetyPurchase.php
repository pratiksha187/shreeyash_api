<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyPurchase extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'safety_item_id',
        'stock_labour_site_id',
        'purchase_date',
        'supplier_name',
        'bill_no',
        'quantity',
        'rate',
        'total_amount',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SafetyItem::class, 'safety_item_id');
    }

    public function stockSite(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'stock_labour_site_id');
    }
}
