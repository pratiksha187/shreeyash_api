<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyIssue extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'safety_request_id',
        'safety_item_id',
        'labour_site_id',
        'project_id',
        'project_task_id',
        'issued_quantity',
        'issued_by',
        'issued_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'issued_quantity' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(SafetyRequest::class, 'safety_request_id');
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
