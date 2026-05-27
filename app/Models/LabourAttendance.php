<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourAttendance extends Model
{
    use HasFactory;

    public const ATTENDANCE_STATUSES = ['present', 'absent', 'half_day'];

    public const APPROVAL_STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'engineer_user_id',
        'labour_site_id',
        'contractor_id',
        'labour_id',
        'attendance_date',
        'status',
        'work_hours',
        'remarks',
        'approval_status',
        'admin_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'work_hours' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_user_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'labour_site_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(Labour::class);
    }
}
