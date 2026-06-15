<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DailyProgressReport extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'dpr_date',
        'site_project',
        'work_summary',
    ];

    protected function casts(): array
    {
        return [
            'dpr_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(DailyProgressReportHour::class);
    }

    public function photos(): HasManyThrough
    {
        return $this->hasManyThrough(
            DailyProgressReportPhoto::class,
            DailyProgressReportHour::class,
            'daily_progress_report_id',
            'daily_progress_report_hour_id',
        );
    }
}
