<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'purchase_date',
        'supplier_name',
        'invoice_no',
        'product_name',
        'unit',
        'quantity',
        'rate',
        'tax_amount',
        'transport_amount',
        'total_amount',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'transport_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }
}
