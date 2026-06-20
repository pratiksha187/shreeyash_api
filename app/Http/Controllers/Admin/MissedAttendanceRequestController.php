<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\MissedAttendanceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MissedAttendanceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'user_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', Rule::in(MissedAttendanceRequest::STATUSES)],
            'request_for' => ['nullable', Rule::in(MissedAttendanceRequest::REQUEST_TYPES)],
        ]);

        $fromDate = isset($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : today()->toDateString();
        $userId = $filters['user_id'] ?? null;
        $status = $filters['status'] ?? null;
        $requestFor = $filters['request_for'] ?? null;

        $baseQuery = MissedAttendanceRequest::query()
            ->forCurrentCompany()
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($requestFor, fn ($query) => $query->where('request_for', $requestFor));

        $requests = (clone $baseQuery)
            ->with('user:id,name,mobile,designation')
            ->orderByDesc('attendance_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $attendanceTimes = Attendance::query()
            ->forCurrentCompany()
            ->whereIn('user_id', $requests->getCollection()->pluck('user_id')->unique())
            ->whereIn('attendance_date', $requests->getCollection()
                ->pluck('attendance_date')
                ->map(fn ($date) => $date->toDateString())
                ->unique())
            ->get()
            ->keyBy(fn (Attendance $attendance) => $attendance->user_id.'|'.$attendance->attendance_date->toDateString());

        return view('admin.missed-requests.index', [
            'requests' => $requests,
            'attendanceTimes' => $attendanceTimes,
            'employees' => User::query()->forCurrentCompany()->employees()->orderBy('name')->get(['id', 'name', 'mobile']),
            'statuses' => MissedAttendanceRequest::STATUSES,
            'requestTypes' => MissedAttendanceRequest::REQUEST_TYPES,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedUserId' => $userId,
            'selectedStatus' => $status,
            'selectedRequestFor' => $requestFor,
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
                'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function update(Request $request, int $missedAttendanceRequestId): RedirectResponse
    {
        // Tenant selection happens in EnsureAdminLoggedIn, after Laravel's implicit bindings.
        $missedAttendanceRequest = MissedAttendanceRequest::query()
            ->forCurrentCompany()
            ->findOrFail($missedAttendanceRequestId);

        $isApproval = $request->input('status') === 'approved';
        $data = $request->validate([
            'request_id' => ['required', 'integer'],
            'status' => ['required', Rule::in(MissedAttendanceRequest::STATUSES)],
            'check_in_time' => [
                Rule::requiredIf($isApproval && in_array($missedAttendanceRequest->request_for, ['clock_in', 'full_day'], true)),
                'nullable',
                'date_format:H:i',
            ],
            'check_out_time' => [
                Rule::requiredIf($isApproval && in_array($missedAttendanceRequest->request_for, ['clock_out', 'full_day'], true)),
                'nullable',
                'date_format:H:i',
            ],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $attendance = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $missedAttendanceRequest->user_id)
            ->whereDate('attendance_date', $missedAttendanceRequest->attendance_date)
            ->first();

        if ($isApproval) {
            $this->validateAttendanceTimes($missedAttendanceRequest, $attendance, $data);
        }

        $missedAttendanceRequest->fill([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_at' => $data['status'] === 'pending' ? null : now(),
        ]);
        $missedAttendanceRequest->save();

        if ($isApproval) {
            $this->applyAttendanceApproval($missedAttendanceRequest, $attendance, $data);
        }

        return back()->with('success', 'Missed attendance request updated successfully.');
    }

    private function applyAttendanceApproval(
        MissedAttendanceRequest $missedAttendanceRequest,
        ?Attendance $attendance,
        array $data
    ): void {
        $remarks = trim(implode(' ', array_filter([
            'Approved missed request:',
            str_replace('_', ' ', $missedAttendanceRequest->request_for).'.',
            $missedAttendanceRequest->reason,
        ])));

        $attendance ??= Attendance::query()->firstOrNew([
            'company_id' => $missedAttendanceRequest->company_id,
            'user_id' => $missedAttendanceRequest->user_id,
            'attendance_date' => $missedAttendanceRequest->attendance_date->toDateString(),
        ]);

        $attendance->fill([
            'status' => 'present',
            'remarks' => $remarks,
        ]);

        if (! empty($data['check_in_time'])) {
            $attendance->check_in_at = $this->utcAttendanceTime($missedAttendanceRequest, $data['check_in_time']);
        }

        if (! empty($data['check_out_time'])) {
            $attendance->check_out_at = $this->utcAttendanceTime($missedAttendanceRequest, $data['check_out_time']);
        }

        $attendance->save();
    }

    private function validateAttendanceTimes(
        MissedAttendanceRequest $missedAttendanceRequest,
        ?Attendance $attendance,
        array $data
    ): void {
        $checkIn = ! empty($data['check_in_time'])
            ? $this->localAttendanceTime($missedAttendanceRequest, $data['check_in_time'])
            : $attendance?->localCheckInAt();
        $checkOut = ! empty($data['check_out_time'])
            ? $this->localAttendanceTime($missedAttendanceRequest, $data['check_out_time'])
            : $attendance?->localCheckOutAt();

        if ($checkIn && $checkOut && $checkOut->lt($checkIn)) {
            throw ValidationException::withMessages([
                'check_out_time' => 'Out time must be the same as or later than in time.',
            ]);
        }
    }

    private function localAttendanceTime(MissedAttendanceRequest $missedAttendanceRequest, string $time): Carbon
    {
        return Carbon::createFromFormat(
            'Y-m-d H:i',
            $missedAttendanceRequest->attendance_date->toDateString().' '.$time,
            Attendance::LOCAL_TIMEZONE
        );
    }

    private function utcAttendanceTime(MissedAttendanceRequest $missedAttendanceRequest, string $time): Carbon
    {
        return $this->localAttendanceTime($missedAttendanceRequest, $time)->utc();
    }
}
