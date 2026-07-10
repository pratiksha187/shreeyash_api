<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
    public const DEFAULT_LEAVE_LIMITS = [
        'casual' => self::LEAVE_TYPE_LIMIT,
        'sick' => self::LEAVE_TYPE_LIMIT,
        'paid' => self::LEAVE_TYPE_LIMIT,
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

    /**
     * @return array{
     *     start: Carbon,
     *     end: Carbon,
     *     limits: array{casual: int, sick: int, paid: int},
     *     total: int,
     *     source: string,
     *     entitlement: LeaveEntitlement|null
     * }
     */
    public static function leaveEntitlementFor(Carbon|string $date, ?User $user): array
    {
        $date = Carbon::parse($date)->startOfDay();
        $period = self::leaveYearPeriodFor($date, $user);
        $entitlement = self::matchingLeaveEntitlement($date, $user);

        if ($entitlement) {
            $period = [
                'start' => $entitlement->leave_year_start->copy()->startOfDay(),
                'end' => $entitlement->leave_year_end->copy()->startOfDay(),
            ];
        }

        $limits = $entitlement?->limits() ?? self::DEFAULT_LEAVE_LIMITS;

        return [
            'start' => $period['start'],
            'end' => $period['end'],
            'limits' => $limits,
            'total' => array_sum($limits),
            'source' => $entitlement ? 'database' : 'default',
            'entitlement' => $entitlement,
        ];
    }

    private static function matchingLeaveEntitlement(Carbon $date, ?User $user): ?LeaveEntitlement
    {
        if (! $user?->id) {
            return null;
        }

        $matching = LeaveEntitlement::query()
            ->forCurrentCompany()
            ->whereDate('leave_year_start', '<=', $date->toDateString())
            ->whereDate('leave_year_end', '>=', $date->toDateString())
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->orderByDesc('user_id')
            ->get();

        if (! $matching instanceof EloquentCollection || $matching->isEmpty()) {
            return null;
        }

        return $matching->firstWhere('user_id', $user->id) ?? $matching->firstWhere('user_id', null);
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
