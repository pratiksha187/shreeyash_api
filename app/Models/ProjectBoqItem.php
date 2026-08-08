<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBoqItem extends Model
{
    use BelongsToCompany;

    public const TYPES = [
        'group' => 'Group',
        'item' => 'Item',
    ];

    protected $fillable = [
        'company_id',
        'project_id',
        'boq_no',
        'parent_boq_no',
        'item_type',
        'group_name',
        'task_name',
        'unit',
        'rate',
        'tender_qty',
        'scope_qty',
        'subcontractor_done_qty',
        'self_done_qty',
        'done_qty',
        'balance_qty',
        'balance_estimate',
        'billed_amount',
        'dpr_unbilled_amount',
        'progress_percent',
        'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'tender_qty' => 'decimal:3',
            'scope_qty' => 'decimal:3',
            'subcontractor_done_qty' => 'decimal:3',
            'self_done_qty' => 'decimal:3',
            'done_qty' => 'decimal:3',
            'balance_qty' => 'decimal:3',
            'balance_estimate' => 'decimal:2',
            'billed_amount' => 'decimal:2',
            'dpr_unbilled_amount' => 'decimal:2',
            'progress_percent' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }
}
