<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\User;
use App\Support\Tenant;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class AttendanceController extends Controller
{
    private const ATTENDANCE_LOCATION_RADIUS_METERS = 150;

    public function clockIn(Request $request): JsonResponse
    {
        $this->normalizeLocationInput($request);
        $today = $this->localToday();

        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $attendance = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($attendance && $attendance->check_in_at) {
            return response()->json([
                'message' => 'You have already clocked in today.',
                'attendance' => $attendance,
            ], 409);
        }

        if ($locationError = $this->locationErrorResponse((float) $data['latitude'], (float) $data['longitude'])) {
            return $locationError;
        }

        $attendance = Attendance::query()->forCurrentCompany()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'attendance_date' => $today,
            ],
            array_merge($data, [
                'status' => 'present',
                'check_in_at' => $this->localNow(),
                'check_out_at' => null,
            ])
        );

        $this->sendForgotLogoutReminderAfterResponse($request->user(), $today);

        return response()->json([
            'message' => 'Clock in successful.',
            'attendance' => $attendance,
        ], 201);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $this->normalizeLocationInput($request);
        $today = $this->localToday();

        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $attendance = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (! $attendance || ! $attendance->check_in_at) {
            return response()->json([
                'message' => 'Please clock in before clocking out.',
            ], 409);
        }

        if ($attendance->check_out_at) {
            return response()->json([
                'message' => 'You have already clocked out today.',
                'attendance' => $attendance,
            ], 409);
        }

        if ($locationError = $this->locationErrorResponse((float) $data['latitude'], (float) $data['longitude'])) {
            return $locationError;
        }

        $attendance->fill(array_merge($data, [
            'check_out_at' => $this->localNow(),
        ]));
        $attendance->save();

        return response()->json([
            'message' => 'Clock out successful.',
            'attendance' => $attendance,
        ]);
    }

    private function normalizeLocationInput(Request $request): void
    {
        $data = [];

        if (! $request->filled('latitude')) {
            foreach (['lat', 'user_latitude'] as $key) {
                if ($request->filled($key)) {
                    $data['latitude'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->filled('longitude')) {
            foreach (['lng', 'long', 'lon', 'user_longitude'] as $key) {
                if ($request->filled($key)) {
                    $data['longitude'] = $request->input($key);
                    break;
                }
            }
        }

        if ($data) {
            $request->merge($data);
        }
    }

    private function locationErrorResponse(float $latitude, float $longitude): ?JsonResponse
    {
        $nearestLocation = $this->nearestLocation($latitude, $longitude);

        if (! $nearestLocation) {
            return response()->json([
                'message' => 'Attendance location is not configured. Please contact admin.',
            ], 422);
        }

        if ($nearestLocation['distance_meters'] <= self::ATTENDANCE_LOCATION_RADIUS_METERS) {
            return null;
        }

        return response()->json([
            'message' => 'You are outside the allowed attendance location. Clock in/out is allowed within 150 meters only.',
            'allowed_radius_meters' => self::ATTENDANCE_LOCATION_RADIUS_METERS,
            'nearest_location' => [
                'id' => $nearestLocation['location']->id,
                'name' => $nearestLocation['location']->name,
                'latitude' => $nearestLocation['location']->latitude,
                'longitude' => $nearestLocation['location']->longitude,
            ],
            'distance_meters' => round($nearestLocation['distance_meters'], 2),
        ], 422);
    }

    /**
     * @return array{location: Location, distance_meters: float}|null
     */
    private function nearestLocation(float $latitude, float $longitude): ?array
    {
        return $this->attendanceLocations()
            ->map(fn (Location $location) => [
                'location' => $location,
                'distance_meters' => $this->distanceInMeters(
                    $latitude,
                    $longitude,
                    (float) $location->latitude,
                    (float) $location->longitude
                ),
            ])
            ->sortBy('distance_meters')
            ->first();
    }

    /**
     * @return Collection<int, Location>
     */
    private function attendanceLocations(): Collection
    {
        $tenant = app(Tenant::class);
        $companyId = $tenant->id();
        $locations = $this->locationsForAttendance(Location::query(), $companyId)->get();

        if ($tenant->connectionName()) {
            $centralLocations = $this->locationsForAttendance(
                Location::on(config('database.default')),
                $companyId
            )->get();

            $locations = $locations->concat($centralLocations);
        }

        return $locations
            ->unique(fn (Location $location) => $location->getConnectionName().':'.$location->id)
            ->values();
    }

    private function locationsForAttendance(Builder $query, ?int $companyId): Builder
    {
        if (! $companyId) {
            return $query;
        }

        return $query->where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhereNull('company_id');
        });
    }

    private function distanceInMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusMeters = 6371000;
        $fromLatitudeRad = deg2rad($fromLatitude);
        $toLatitudeRad = deg2rad($toLatitude);
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos($fromLatitudeRad) * cos($toLatitudeRad) * sin($longitudeDelta / 2) ** 2;

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function me(Request $request): JsonResponse
    {
        $attendance = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', $this->localToday())
            ->first();

        return response()->json([
            'user' => $request->user(),
            'attendance' => $attendance,
        ]);
    }

    public function loginReminder(Request $request): JsonResponse
    {
        $timezone = Attendance::LOCAL_TIMEZONE;
        $now = Carbon::now($timezone);
        $today = $now->toDateString();
        $windowStart = $now->copy()->setTime(9, 0);
        $windowEnd = $now->copy()->setTime(9, 15, 59);

        $attendance = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $hasClockedIn = (bool) $attendance?->check_in_at;
        $isReminderWindow = $now->betweenIncluded($windowStart, $windowEnd);
        $shouldNotify = $isReminderWindow && ! $hasClockedIn;

        return response()->json([
            'message' => $shouldNotify
                ? 'Login reminder is active.'
                : 'Login reminder is not needed right now.',
            'should_notify' => $shouldNotify,
            'notification' => [
                'title' => 'Attendance Reminder',
                'body' => 'Please clock in for today attendance.',
            ],
            'reminder_key' => 'login-reminder-' . $request->user()->id . '-' . $today,
            'date' => $today,
            'current_time' => $now->format('H:i:s'),
            'timezone' => $timezone,
            'window' => [
                'from' => $windowStart->format('H:i:s'),
                'to' => $windowEnd->format('H:i:s'),
            ],
            'is_reminder_window' => $isReminderWindow,
            'has_clocked_in' => $hasClockedIn,
            'attendance' => $attendance,
        ]);
    }

    public function dailyReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filters = $validator->validated();
        $fromDate = isset($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : $this->localToday();

        $attendances = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->orderByDesc('attendance_date')
            ->get();

        $leaveAttendances = $attendances
            ->where('status', 'leave')
            ->values();
        $leaveReportEntries = $leaveAttendances
            ->map(fn (Attendance $attendance) => $this->leaveReportEntry($attendance))
            ->values();

        return response()->json([
            'message' => 'Daily attendance report fetched successfully.',
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => [
                'total_days' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'leave' => $leaveAttendances->count(),
                'half_day' => $attendances->where('status', 'half_day')->count(),
            ],
            'leave_report' => [
                'total_days' => $leaveAttendances->count(),
                'leaves' => $leaveReportEntries,
            ],
            'attendances' => $attendances,
        ]);
    }

    public function applyLeave(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_date' => ['required', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'leave_type' => ['nullable', Rule::in(array_keys(Attendance::LEAVE_TYPES))],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $fromDate = Carbon::parse($data['from_date'])->toDateString();
        $toDate = isset($data['to_date'])
            ? Carbon::parse($data['to_date'])->toDateString()
            : $fromDate;
        $leaveType = $data['leave_type'] ?? 'casual';

        $dates = collect(CarbonPeriod::create($fromDate, $toDate))
            ->map(fn (Carbon $date) => $date->toDateString());

        $requestedLeaveDatesByPeriod = $dates
            ->groupBy(function (string $date) use ($request) {
                $period = Attendance::leaveEntitlementFor($date, $request->user());

                return $period['start']->toDateString().'|'.$period['end']->toDateString();
            });

        foreach ($requestedLeaveDatesByPeriod as $periodKey => $periodDates) {
            [$periodStart, $periodEnd] = explode('|', $periodKey);
            $entitlement = Attendance::leaveEntitlementFor($periodDates->first(), $request->user());
            $leaveTypeLimit = $entitlement['limits'][$leaveType] ?? 0;
            $requestedLeaveCount = $periodDates->count();

            $usedLeaveCount = Attendance::query()
                ->forCurrentCompany()
                ->where('user_id', $request->user()->id)
                ->where('status', 'leave')
                ->where('leave_approval_status', '!=', 'rejected')
                ->where(function ($query) use ($leaveType) {
                    $query->where('leave_type', $leaveType);

                    if ($leaveType === 'casual') {
                        $query->orWhereNull('leave_type');
                    }
                })
                ->whereBetween('attendance_date', [$periodStart, $periodEnd])
                ->whereNotIn('attendance_date', $dates->all())
                ->count();

            if (($usedLeaveCount + $requestedLeaveCount) > $leaveTypeLimit) {
                $message = $entitlement['source'] === 'ineligible'
                    ? 'Leave can be applied only after completing '.Attendance::LEAVE_ELIGIBILITY_MONTHS.' months from joining date.'
                    : 'You can apply only '.$leaveTypeLimit.' '.Attendance::LEAVE_TYPES[$leaveType].' days in your leave year.';

                return response()->json([
                    'message' => $message,
                    'leave_type' => $leaveType,
                    'leave_type_label' => Attendance::LEAVE_TYPES[$leaveType],
                    'leave_year_start' => $periodStart,
                    'leave_year_end' => $periodEnd,
                    'leave_eligibility_date' => $entitlement['eligibility_date']?->toDateString(),
                    'is_leave_eligible' => $entitlement['is_eligible'],
                    'yearly_leave_limit' => $entitlement['total'],
                    'leave_type_limit' => $leaveTypeLimit,
                    'leave_limits' => $entitlement['limits'],
                    'leave_entitlement_source' => $entitlement['source'],
                    'used_leaves' => $usedLeaveCount,
                    'requested_leaves' => $requestedLeaveCount,
                    'remaining_leaves' => max(0, $leaveTypeLimit - $usedLeaveCount),
                ], 422);
            }
        }

        $existingWorkedDays = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->whereIn('attendance_date', $dates)
            ->where(function ($query) {
                $query->whereNotNull('check_in_at')
                    ->orWhereNotNull('check_out_at');
            })
            ->pluck('attendance_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values();

        if ($existingWorkedDays->isNotEmpty()) {
            return response()->json([
                'message' => 'Leave cannot be applied for dates where attendance is already marked.',
                'dates' => $existingWorkedDays,
            ], 409);
        }

        $attendances = $dates->map(function (string $date) use ($request, $data, $leaveType) {
            return Attendance::query()->forCurrentCompany()->updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'attendance_date' => $date,
                ],
                [
                    'status' => 'leave',
                    'leave_approval_status' => 'pending',
                    'leave_type' => $leaveType,
                    'check_in_at' => null,
                    'check_out_at' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'remarks' => $data['remarks'] ?? null,
                ]
            );
        });

        $entitlement = Attendance::leaveEntitlementFor($fromDate, $request->user());

        return response()->json([
            'message' => 'Leave applied successfully.',
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'leave_type' => $leaveType,
            'leave_type_label' => Attendance::LEAVE_TYPES[$leaveType],
            'leave_entitlement' => [
                'leave_year_start' => $entitlement['start']->toDateString(),
                'leave_year_end' => $entitlement['end']->toDateString(),
                'yearly_leave_limit' => $entitlement['total'],
                'leave_type_limit' => $entitlement['limits'][$leaveType] ?? 0,
                'leave_limits' => $entitlement['limits'],
                'source' => $entitlement['source'],
                'is_eligible' => $entitlement['is_eligible'],
                'eligibility_date' => $entitlement['eligibility_date']?->toDateString(),
            ],
            'attendances' => $attendances,
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['present', 'absent', 'leave', 'half_day'])],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date', 'after_or_equal:check_in_at'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $attendance = Attendance::query()->forCurrentCompany()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'attendance_date' => $this->localToday(),
            ],
            $data
        );

        return response()->json([
            'message' => 'Attendance updated successfully.',
            'attendance' => $attendance,
        ]);
    }

    private function localToday(): string
    {
        return Carbon::now(Attendance::LOCAL_TIMEZONE)->toDateString();
    }

    private function localNow(): Carbon
    {
        return Carbon::now(Attendance::LOCAL_TIMEZONE);
    }

    private function sendForgotLogoutReminderAfterResponse(?User $user, string $today): void
    {
        if (! $user) {
            return;
        }

        app()->terminating(function () use ($user, $today) {
            $this->sendForgotLogoutReminderIfNeeded($user, $today);
        });
    }

    private function sendForgotLogoutReminderIfNeeded(User $user, string $today): void
    {
        if (! $user?->email) {
            return;
        }

        $missedLogout = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', '<', $today)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->whereNull('logout_reminder_sent_at')
            ->orderByDesc('attendance_date')
            ->first();

        if (! $missedLogout) {
            return;
        }

        $missedDate = $missedLogout->attendance_date?->format('d M Y') ?? $missedLogout->attendance_date;
        $checkInTime = $missedLogout->localCheckInAt()?->format('h:i A') ?? 'not available';

        try {
            if (in_array(config('mail.default'), ['log', 'array'], true)) {
                Log::warning('Forgot logout reminder email was not sent because mail driver is not configured for real delivery.', [
                    'mail_driver' => config('mail.default'),
                    'user_id' => $user->id,
                    'attendance_id' => $missedLogout->id,
                    'to' => $user->email,
                    'cc' => $this->logoutReminderCcEmails(),
                ]);

                return;
            }

            Mail::raw(
                "Dear {$user->name},\n\n"
                ."Our attendance system shows that you logged in on {$missedDate} at {$checkInTime}, but your logout was not marked.\n\n"
                ."Please submit a missed logout request for {$missedDate} and attach/send proper proof for your going time on that day.\n\n"
                ."This reminder was sent automatically when you logged in today.\n\n"
                ."Regards,\nAttendance Admin",
                function ($message) use ($user, $missedDate) {
                    $message->to($user->email, $user->name)
                        ->cc($this->logoutReminderCcEmails())
                        ->subject('Logout not marked for '.$missedDate);
                }
            );

            $missedLogout->forceFill([
                'logout_reminder_sent_at' => $this->localNow(),
            ])->save();
        } catch (Throwable $exception) {
            Log::warning('Failed to send forgot logout reminder email.', [
                'user_id' => $user->id,
                'attendance_id' => $missedLogout->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function logoutReminderCcEmails(): array
    {
        return collect(explode(',', (string) env('LOGOUT_REMINDER_CC')))
            ->map(fn (string $email) => trim($email))
            ->filter()
            ->values()
            ->all();
    }

    private function leaveReportEntry(Attendance $attendance): array
    {
        $approvalStatus = $attendance->leave_approval_status ?: 'pending';

        return array_merge($attendance->toArray(), [
            'attendance_status' => $attendance->status,
            'status' => $approvalStatus,
            'approval_status' => $approvalStatus,
            'leave_approval_status' => $approvalStatus,
            'leave_type' => $attendance->leave_type ?? 'casual',
            'leave_type_label' => Attendance::LEAVE_TYPES[$attendance->leave_type ?? 'casual'] ?? Attendance::LEAVE_TYPES['casual'],
            'is_approved' => $approvalStatus === 'approved',
            'is_rejected' => $approvalStatus === 'rejected',
            'is_pending' => $approvalStatus === 'pending',
        ]);
    }
}
