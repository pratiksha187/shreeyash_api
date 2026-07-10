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

        if ($request->filled('leave_type')) {
            $query->where(function ($query) use ($request) {
                $query->where('leave_type', $request->input('leave_type'));

                if ($request->input('leave_type') === 'casual') {
                    $query->orWhereNull('leave_type');
                }
            });
        }

        $leaves = $query->with('user')->paginate(20)->appends($request->query());
        $leaveUsage = $leaves->getCollection()
            ->mapWithKeys(function (Attendance $leave) {
                if (! $leave->attendance_date) {
                    return [];
                }

                $entitlement = Attendance::leaveEntitlementFor($leave->attendance_date, $leave->user);
                $approvedLeaves = Attendance::query()
                    ->forCurrentCompany()
                    ->where('user_id', $leave->user_id)
                    ->where('status', 'leave')
                    ->where('leave_approval_status', 'approved')
                    ->whereBetween('attendance_date', [
                        $entitlement['start']->toDateString(),
                        $entitlement['end']->toDateString(),
                    ])
                    ->get();
                $approvedLeavesByType = collect(array_keys(Attendance::LEAVE_TYPES))
                    ->mapWithKeys(fn (string $type) => [
                        $type => $approvedLeaves
                            ->filter(fn (Attendance $attendance) => ($attendance->leave_type ?? 'casual') === $type)
                            ->count(),
                    ]);

                return [
                    $leave->id => [
                        'start' => $entitlement['start'],
                        'end' => $entitlement['end'],
                        'used' => $approvedLeaves->count(),
                        'remaining' => max(0, $entitlement['total'] - $approvedLeaves->count()),
                        'total_limit' => $entitlement['total'],
                        'limits' => $entitlement['limits'],
                        'source' => $entitlement['source'],
                        'is_eligible' => $entitlement['is_eligible'],
                        'eligibility_date' => $entitlement['eligibility_date'],
                        'by_type' => $approvedLeavesByType,
                    ],
                ];
            });

        return view('admin.leave-requests.index', [
            'leaves' => $leaves,
            'leaveUsage' => $leaveUsage,
            'leaveTypes' => Attendance::LEAVE_TYPES,
            'employees' => \App\Models\User::query()->forCurrentCompany()->employees()->orderBy('name')->get(),
            'selectedEmployeeId' => $request->input('employee_id'),
            'selectedMonth' => $request->input('month'),
            'selectedStatus' => $request->input('status'),
            'selectedLeaveType' => $request->input('leave_type'),
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
            'leave_type' => ['required', Rule::in(array_keys(Attendance::LEAVE_TYPES))],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $leaveRequest = Attendance::query()
            ->forCurrentCompany()
            ->with('user')
            ->where('status', 'leave')
            ->findOrFail($leave);

        if ($data['status'] === 'approved') {
            $entitlement = Attendance::leaveEntitlementFor($leaveRequest->attendance_date, $leaveRequest->user);
            $leaveType = $data['leave_type'];
            $leaveTypeLimit = $entitlement['limits'][$leaveType] ?? 0;

            if (! $entitlement['is_eligible']) {
                return redirect()
                    ->route('admin.leave-requests.index')
                    ->with('error', 'This employee can get leave only after completing '.Attendance::LEAVE_ELIGIBILITY_MONTHS.' months from joining date. Eligible from '.$entitlement['eligibility_date']?->format('d M Y').'.');
            }

            $approvedLeavesForType = Attendance::query()
                ->forCurrentCompany()
                ->where('user_id', $leaveRequest->user_id)
                ->where('status', 'leave')
                ->where('leave_approval_status', 'approved')
                ->where(function ($query) use ($leaveType) {
                    $query->where('leave_type', $leaveType);

                    if ($leaveType === 'casual') {
                        $query->orWhereNull('leave_type');
                    }
                })
                ->whereBetween('attendance_date', [
                    $entitlement['start']->toDateString(),
                    $entitlement['end']->toDateString(),
                ])
                ->whereKeyNot($leaveRequest->getKey())
                ->count();

            if ($approvedLeavesForType >= $leaveTypeLimit) {
                return redirect()
                    ->route('admin.leave-requests.index')
                    ->with('error', 'This employee already has '.$leaveTypeLimit.' '.Attendance::LEAVE_TYPES[$leaveType].' days from '.$entitlement['start']->format('d M Y').' to '.$entitlement['end']->format('d M Y').'.');
            }
        }

        $leaveRequest->forceFill([
            'leave_approval_status' => $data['status'],
            'leave_type' => $data['leave_type'],
            'leave_approved_at' => $data['status'] === 'approved' ? now() : null,
            'leave_admin_note' => $data['admin_note'] ?? null,
        ])->save();

        return redirect()
            ->route('admin.leave-requests.index')
            ->with('success', 'Leave request updated successfully.');
    }
}
