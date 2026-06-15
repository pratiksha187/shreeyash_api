<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissedAttendanceRequest extends Model
{
    use BelongsToCompany, HasFactory;

    public const REQUEST_TYPES = ['clock_in', 'clock_out', 'full_day'];

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'company_id',
        'user_id',
        'attendance_date',
        'request_for',
        'reason',
        'status',
        'admin_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
