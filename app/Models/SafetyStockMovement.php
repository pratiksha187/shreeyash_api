<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyStockMovement extends Model
{
    use BelongsToCompany;

    public const PURCHASE_IN = 'purchase_in';
    public const ISSUE_OUT = 'issue_out';

    protected $fillable = [
        'company_id',
        'safety_item_id',
        'labour_site_id',
        'project_id',
        'project_task_id',
        'type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'balance_after' => 'decimal:2',
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
