<?php

namespace App\Models;

use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyProgressReportPhoto extends Model
{
    use HasFactory, UsesTenantConnection;

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

    public function publicUrl(): ?string
    {
        $path = $this->publicStoragePath();

        return $path ? asset('storage/' . $path) : null;
    }

    public function publicStoragePath(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        $path = str_replace('\\', '/', ltrim($this->photo_path, '/\\'));

        foreach ([
            '#^public/storage/#',
            '#^storage/app/public/#',
            '#^public/#',
            '#^storage/#',
        ] as $pattern) {
            $path = preg_replace($pattern, '', $path) ?? $path;
        }

        return str_contains($path, '..') ? null : $path;
    }
}
