<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PurchaseWorkflow extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'requisition_no',
        'requisition_date',
        'indent_no',
        'material_name',
        'unit',
        'quantity',
        'vendor_enquiry_no',
        'vendor_names',
        'quotation_summary',
        'selected_vendor',
        'quoted_amount',
        'approval_limit',
        'approval_status',
        'po_no',
        'po_date',
        'po_status',
        'grn_no',
        'grn_date',
        'grn_status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'requisition_date' => 'date',
            'po_date' => 'date',
            'grn_date' => 'date',
            'quantity' => 'decimal:3',
            'quoted_amount' => 'decimal:2',
            'approval_limit' => 'decimal:2',
        ];
    }
}
