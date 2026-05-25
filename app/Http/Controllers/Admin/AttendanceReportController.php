<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $fromDate = isset($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : today()->toDateString();

        $attendances = Attendance::query()
            ->with('user:id,name,email,mobile,designation')
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->orderByDesc('attendance_date')
            ->get();

        $leaveAttendances = $attendances
            ->where('status', 'leave')
            ->values();

        $employeeReports = User::query()
            ->select([
                'id',
                'name',
                'email',
                'mobile',
                'designation',
                'join_date',
            ])
            ->with(['attendances' => function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('attendance_date', [$fromDate, $toDate])
                    ->orderByDesc('attendance_date');
            }])
            ->orderBy('name')
            ->get();

        return view('admin.attendance-reports.index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'summary' => [
                'total_days' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'leave' => $leaveAttendances->count(),
                'half_day' => $attendances->where('status', 'half_day')->count(),
            ],
            'leaveAttendances' => $leaveAttendances,
            'employeeReports' => $employeeReports,
        ]);
    }
}
