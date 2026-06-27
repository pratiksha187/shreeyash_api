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
    public const LEAVE_TYPE_LIMIT = 4;
    public const LEAVE_TYPES = [
        'casual' => 'Casual Leave',
        'sick' => 'Sick Leave',
        'paid' => 'Paid Leave',
    ];

    public static function leaveYearPeriodFor(Carbon|string $date, ?User $user): array
    {
        $date = Carbon::parse($date)->startOfDay();

        if (! $user?->join_date) {
            return [
                'start' => $date->copy()->startOfYear(),
                'end' => $date->copy()->endOfYear()->startOfDay(),
            ];
        }

        $joinDate = $user->join_date->copy();
        $start = self::anniversaryDateForYear($joinDate, $date->year);

        if ($date->lt($start)) {
            $start = self::anniversaryDateForYear($joinDate, $date->year - 1);
        }

        $end = self::anniversaryDateForYear($joinDate, $start->year + 1)->subDay();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private static function anniversaryDateForYear(Carbon $joinDate, int $year): Carbon
    { 
        $day = min($joinDate->day, Carbon::create($year, $joinDate->month, 1)->daysInMonth);

        return Carbon::create($year, $joinDate->month, $day)->startOfDay();
    }

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
        'leave_type',
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
