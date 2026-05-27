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
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($requestFor, fn ($query) => $query->where('request_for', $requestFor));

        return view('admin.missed-requests.index', [
            'requests' => (clone $baseQuery)
                ->with('user:id,name,mobile,designation')
                ->orderByDesc('attendance_date')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'employees' => User::query()->orderBy('name')->get(['id', 'name', 'mobile']),
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

    public function update(Request $request, MissedAttendanceRequest $missedAttendanceRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(MissedAttendanceRequest::STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $missedAttendanceRequest->fill([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_at' => $data['status'] === 'pending' ? null : now(),
        ]);
        $missedAttendanceRequest->save();

        if ($data['status'] === 'approved') {
            $this->applyAttendanceApproval($missedAttendanceRequest);
        }

        return back()->with('success', 'Missed attendance request updated successfully.');
    }

    private function applyAttendanceApproval(MissedAttendanceRequest $missedAttendanceRequest): void
    {
        $remarks = trim(implode(' ', array_filter([
            'Approved missed request:',
            str_replace('_', ' ', $missedAttendanceRequest->request_for) . '.',
            $missedAttendanceRequest->reason,
        ])));

        $attendance = Attendance::query()->firstOrNew([
            'user_id' => $missedAttendanceRequest->user_id,
            'attendance_date' => $missedAttendanceRequest->attendance_date->toDateString(),
        ]);

        $attendance->fill([
            'status' => 'present',
            'remarks' => $remarks,
        ]);
        $attendance->save();
    }
}
