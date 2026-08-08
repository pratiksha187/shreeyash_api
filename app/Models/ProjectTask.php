<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTask extends Model
{
    use BelongsToCompany;

    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'blocked' => 'Blocked',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const STRUCTURE_TYPES = [
        'phase' => 'Phase',
        'layer' => 'Layer',
        'task' => 'Task',
        'sub_task' => 'Sub-task',
    ];

    protected $fillable = [
        'company_id',
        'project_id',
        'parent_task_id',
        'structure_type',
        'sort_order',
        'assigned_engineer_id',
        'assigned_supervisor_id',
        'created_by',
        'material_id',
        'title',
        'work_area',
        'boq_item_number',
        'sor_item_number',
        'priority',
        'status',
        'start_date',
        'due_date',
        'completed_at',
        'estimated_hours',
        'quantity_unit',
        'material_template',
        'opening_stock_qty',
        'receipt_qty',
        'issue_consumption_qty',
        'return_qty',
        'closing_stock_qty',
        'planned_material_qty',
        'planned_labour_count',
        'planned_machinery_count',
        'variance_limit_percent',
        'planned_quantity',
        'executed_quantity',
        'rate',
        'planned_cost',
        'actual_cost',
        'actual_hours',
        'progress_percent',
        'description',
        'completion_note',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id')->orderBy('sort_order')->orderBy('id');
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_engineer_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_supervisor_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectTaskUpdate::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
            'estimated_hours' => 'decimal:2',
            'opening_stock_qty' => 'decimal:3',
            'receipt_qty' => 'decimal:3',
            'issue_consumption_qty' => 'decimal:3',
            'return_qty' => 'decimal:3',
            'closing_stock_qty' => 'decimal:3',
            'planned_material_qty' => 'decimal:3',
            'planned_labour_count' => 'integer',
            'planned_machinery_count' => 'integer',
            'variance_limit_percent' => 'decimal:2',
            'planned_quantity' => 'decimal:3',
            'executed_quantity' => 'decimal:3',
            'rate' => 'decimal:2',
            'planned_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'progress_percent' => 'integer',
        ];
    }
}
