<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use BelongsToCompany;

    public const STATUSES = [
        'planned' => 'Planned',
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'company_id',
        'planning_manager_id',
        'name',
        'code',
        'client_name',
        'site_location',
        'work_order_number',
        'work_order_date',
        'boq_reference',
        'sor_reference',
        'start_date',
        'target_date',
        'completed_at',
        'budget_amount',
        'quantity_unit',
        'planned_quantity',
        'executed_quantity',
        'estimated_cost',
        'actual_cost',
        'status',
        'progress_percent',
        'description',
    ];

    public function planningManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planning_manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function boqItems(): HasMany
    {
        return $this->hasMany(ProjectBoqItem::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_date' => 'date',
            'completed_at' => 'date',
            'work_order_date' => 'date',
            'budget_amount' => 'decimal:2',
            'planned_quantity' => 'decimal:3',
            'executed_quantity' => 'decimal:3',
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'progress_percent' => 'integer',
        ];
    }
}
