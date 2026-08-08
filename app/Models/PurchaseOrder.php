<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'po_no',
        'po_date',
        'delivery_date',
        'supplier_name',
        'supplier_address',
        'supplier_gstin',
        'supplier_ref',
        'supplier_tds_section',
        'tds_percent',
        'tds_amount',
        'net_payable_amount',
        'e_invoice_applicable',
        'e_way_bill_applicable',
        'vendor_reconciliation_status',
        'auditor_export_note',
        'other_reference',
        'dispatched_through',
        'destination',
        'consignee_name',
        'delivery_location',
        'consignee_gstin',
        'subtotal',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'terms',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'tds_percent' => 'decimal:2',
            'tds_amount' => 'decimal:2',
            'net_payable_amount' => 'decimal:2',
            'e_invoice_applicable' => 'boolean',
            'e_way_bill_applicable' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
