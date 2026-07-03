<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPurchase extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'material_id',
        'stock_labour_site_id',
        'purchase_date',
        'supplier_name',
        'invoice_no',
        'product_name',
        'size',
        'pcs',
        'weight_kg',
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
            'pcs' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'transport_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function stockSite(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'stock_labour_site_id');
    }
}
