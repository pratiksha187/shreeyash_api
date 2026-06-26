<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToCompany, HasFactory;

    public const LOCAL_TIMEZONE = 'Asia/Kolkata';
    public const YEARLY_LEAVE_LIMIT = 12;

    protected $fillable = [
        'company_id',
        'user_id',
        'attendance_date',
        'status',
        'check_in_at',
        'check_out_at',
        'latitude',
        'longitude',
        'remarks',
        'leave_approval_status',
        'leave_approved_at',
        'leave_admin_note',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'leave_approved_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function localCheckInAt(): ?Carbon
    {
        return $this->localTimestamp('check_in_at');
    }

    public function localCheckOutAt(): ?Carbon
    {
        return $this->localTimestamp('check_out_at');
    }

    private function localTimestamp(string $attribute): ?Carbon
    {
        $value = $this->getRawOriginal($attribute);

        if (! $value) {
            return null;
        }

        return Carbon::parse($value, self::LOCAL_TIMEZONE);
    }
}
