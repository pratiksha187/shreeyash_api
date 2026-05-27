<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyProgressReportHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_progress_report_id',
        'hour_number',
        'work_time',
        'remark',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(DailyProgressReport::class, 'daily_progress_report_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DailyProgressReportPhoto::class);
    }
}
