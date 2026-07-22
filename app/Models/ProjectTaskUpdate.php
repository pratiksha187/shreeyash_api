<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectTaskUpdate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'project_task_id',
        'user_id',
        'status',
        'progress_percent',
        'actual_hours',
        'remark',
        'photo_path',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'actual_hours' => 'decimal:2',
        ];
    }
}
