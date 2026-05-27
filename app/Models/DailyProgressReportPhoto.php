<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyProgressReportPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_progress_report_hour_id',
        'photo_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function hour(): BelongsTo
    {
        return $this->belongsTo(DailyProgressReportHour::class, 'daily_progress_report_hour_id');
    }
}
