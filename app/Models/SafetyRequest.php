<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyRequest extends Model
{
    use BelongsToCompany;

    public const STATUSES = [
        'pending',
        'approved',
        'partially_approved',
        'rejected',
        'purchase_required',
        'issued',
        'cancelled',
    ];

    protected $fillable = [
        'company_id',
        'safety_item_id',
        'labour_site_id',
        'project_id',
        'project_task_id',
        'request_date',
        'requested_quantity',
        'approved_quantity',
        'issued_quantity',
        'requested_by',
        'priority',
        'status',
        'purpose',
        'admin_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'requested_quantity' => 'decimal:2',
            'approved_quantity' => 'decimal:2',
            'issued_quantity' => 'decimal:2',
            'reviewed_at' => 'datetime',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(SafetyIssue::class);
    }
}
