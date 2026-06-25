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

        return view('admin.leave-requests.index', [
            'leaves' => $leaves,
            'employees' => \App\Models\User::query()->employees()->orderBy('name')->get(),
            'selectedEmployeeId' => $request->input('employee_id'),
            'selectedMonth' => $request->input('month'),
            'selectedStatus' => $request->input('status'),
            'statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }

    public function show(Attendance $leave): RedirectResponse
    {
        return redirect()->route('admin.leave-requests.index');
    }

    public function update(Request $request, Attendance $leave): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $leave->forceFill([
            'leave_approval_status' => $data['status'],
            'leave_approved_at' => $data['status'] === 'approved' ? now() : null,
            'leave_admin_note' => $data['admin_note'] ?? null,
        ])->save();

        return back()->with('success', 'Leave request updated successfully.');
    }
}
