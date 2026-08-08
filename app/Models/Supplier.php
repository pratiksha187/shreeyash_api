<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'contact_person',
        'mobile',
        'email',
        'gstin',
        'gst_registration_type',
        'gst_return_status',
        'tds_section',
        'tds_percent',
        'e_invoice_applicable',
        'e_way_bill_applicable',
        'vendor_reconciliation_status',
        'auditor_export_note',
        'address',
        'default_dispatched_through',
        'default_destination',
        'default_terms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tds_percent' => 'decimal:2',
            'e_invoice_applicable' => 'boolean',
            'e_way_bill_applicable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
