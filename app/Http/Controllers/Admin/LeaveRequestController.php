<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = Attendance::query()
            ->forCurrentCompany()
            ->where('status', 'leave')
            ->orderByDesc('attendance_date');

        if ($request->filled('employee_id')) {
            $query->where('user_id', $request->input('employee_id'));
        }

        if ($request->filled('month')) {
            $query->where('attendance_date', 'like', $request->input('month').'%');
        }

        if ($request->filled('status')) {
            $query->where('leave_approval_status', $request->input('status'));
        }

        $leaves = $query->with('user')->paginate(20)->appends($request->query());
        $leaveUsage = $leaves->getCollection()
            ->mapWithKeys(function (Attendance $leave) {
                if (! $leave->attendance_date) {
                    return [];
                }

                $period = Attendance::leaveYearPeriodFor($leave->attendance_date, $leave->user);
                $usedLeaves = Attendance::query()
                    ->forCurrentCompany()
                    ->where('user_id', $leave->user_id)
                    ->where('status', 'leave')
                    ->where('leave_approval_status', 'approved')
                    ->whereBetween('attendance_date', [
                        $period['start']->toDateString(),
                        $period['end']->toDateString(),
                    ])
                    ->count();

                return [
                    $leave->id => [
                        'start' => $period['start'],
                        'end' => $period['end'],
                        'used' => $usedLeaves,
                        'remaining' => max(0, Attendance::YEARLY_LEAVE_LIMIT - $usedLeaves),
                    ],
                ];
            });

        return view('admin.leave-requests.index', [
            'leaves' => $leaves,
            'leaveUsage' => $leaveUsage,
            'yearlyLeaveLimit' => Attendance::YEARLY_LEAVE_LIMIT,
            'employees' => \App\Models\User::query()->forCurrentCompany()->employees()->orderBy('name')->get(),
            'selectedEmployeeId' => $request->input('employee_id'),
            'selectedMonth' => $request->input('month'),
            'selectedStatus' => $request->input('status'),
            'statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }

    public function show(string $leave): RedirectResponse
    {
        return redirect()->route('admin.leave-requests.index');
    }

    public function update(Request $request, string $leave): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $leaveRequest = Attendance::query()
            ->forCurrentCompany()
            ->with('user')
            ->where('status', 'leave')
            ->findOrFail($leave);

        if ($data['status'] === 'approved') {
            $period = Attendance::leaveYearPeriodFor($leaveRequest->attendance_date, $leaveRequest->user);
            $approvedLeavesThisYear = Attendance::query()
                ->forCurrentCompany()
                ->where('user_id', $leaveRequest->user_id)
                ->where('status', 'leave')
                ->where('leave_approval_status', 'approved')
                ->whereBetween('attendance_date', [
                    $period['start']->toDateString(),
                    $period['end']->toDateString(),
                ])
                ->whereKeyNot($leaveRequest->getKey())
                ->count();

            if ($approvedLeavesThisYear >= Attendance::YEARLY_LEAVE_LIMIT) {
                return redirect()
                    ->route('admin.leave-requests.index')
                    ->with('error', 'This employee already has '.Attendance::YEARLY_LEAVE_LIMIT.' approved leaves from '.$period['start']->format('d M Y').' to '.$period['end']->format('d M Y').'.');
            }
        }

        $leaveRequest->forceFill([
            'leave_approval_status' => $data['status'],
            'leave_approved_at' => $data['status'] === 'approved' ? now() : null,
            'leave_admin_note' => $data['admin_note'] ?? null,
        ])->save();

        return redirect()
            ->route('admin.leave-requests.index')
            ->with('success', 'Leave request updated successfully.');
    }
}
