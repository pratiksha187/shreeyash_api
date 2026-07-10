<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    public function today(Request $request): View
    {
        $selectedDate = $request->validate([
            'date' => ['nullable', 'date'],
        ])['date'] ?? Carbon::now(Attendance::LOCAL_TIMEZONE)->toDateString();
        $selectedDate = Carbon::parse($selectedDate, Attendance::LOCAL_TIMEZONE)->toDateString();

        $attendances = Attendance::query()
            ->forCurrentCompany()
            ->with('user:id,name,email,mobile,designation')
            ->whereDate('attendance_date', $selectedDate)
            ->get()
            ->keyBy('user_id');

        $employeesQuery = User::query()
            ->forCurrentCompany()
            ->employees()
            ->select([
                'id',
                'name',
                'email',
                'mobile',
                'designation',
                'is_active',
            ])
            ->orderBy('name');

        $allEmployees = (clone $employeesQuery)->get();
        $employees = $employeesQuery
            ->paginate(15)
            ->appends($request->query())
            ->through(fn (User $employee) => $this->withTodayAttendance($employee, $attendances));

        $statusCounts = [
            'present' => 0,
            'leave' => 0,
            'absent' => 0,
            'half_day' => 0,
            'not_marked' => 0,
        ];

        foreach ($allEmployees as $employee) {
            $attendance = $attendances->get($employee->id);
            $status = $attendance?->status ?? 'not_marked';

            if (! array_key_exists($status, $statusCounts)) {
                $statusCounts[$status] = 0;
            }

            $statusCounts[$status]++;
        }

        return view('admin.attendance-reports.today', [
            'selectedDate' => $selectedDate,
            'employees' => $employees,
            'summary' => [
                'total_employees' => $allEmployees->count(),
                'present' => $statusCounts['present'],
                'leave' => $statusCounts['leave'],
                'absent' => $statusCounts['absent'],
                'half_day' => $statusCounts['half_day'],
                'not_marked' => $statusCounts['not_marked'],
            ],
        ]);
    }

    public function index(Request $request): View|StreamedResponse
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
            : now()->endOfMonth()->toDateString();

        if ($request->boolean('export')) {
            return $this->downloadAttendanceReport($fromDate, $toDate);
        }

        $attendances = Attendance::query()
            ->forCurrentCompany()
            ->with('user:id,name,email,mobile,designation')
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->orderByDesc('attendance_date')
            ->get();

        $leaveAttendances = $attendances
            ->where('status', 'leave')
            ->values();

        $employeeReports = User::query()
            ->forCurrentCompany()
            ->employees()
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

    public function export(Request $request): StreamedResponse
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
            : now()->endOfMonth()->toDateString();

        return $this->downloadAttendanceReport($fromDate, $toDate);
    }

    private function withTodayAttendance(User $employee, Collection $attendances): User
    {
        $employee->setRelation('todayAttendance', $attendances->get($employee->id));

        return $employee;
    }

    private function downloadAttendanceReport(string $fromDate, string $toDate): StreamedResponse
    {
        $attendances = Attendance::query()
            ->forCurrentCompany()
            ->with('user:id,name,email,mobile,designation')
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->orderBy('attendance_date')
            ->orderBy('id')
            ->get();

        $filename = 'attendance-report-' . $fromDate . '-to-' . $toDate . '.csv';

        return response()->streamDownload(function () use ($attendances) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sr. No.', 'Date', 'Employee', 'Email', 'Mobile', 'Designation', 'Status', 'Login', 'Logout', 'Total Hours', 'Remarks']);

            foreach ($attendances as $index => $attendance) {
                $checkIn = $attendance->localCheckInAt();
                $checkOut = $attendance->localCheckOutAt();

                fputcsv($handle, [
                    $index + 1,
                    $attendance->attendance_date?->format('d-m-Y') ?? '',
                    $attendance->user?->name ?? '',
                    $attendance->user?->email ?? '',
                    $attendance->user?->mobile ?? '',
                    $attendance->user?->designation ?? '',
                    str_replace('_', ' ', ucfirst($attendance->status)),
                    $checkIn?->format('h:i A') ?? '',
                    $checkOut?->format('h:i A') ?? '',
                    $this->totalHours($checkIn, $checkOut),
                    $attendance->remarks ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function totalHours(?Carbon $checkIn, ?Carbon $checkOut): string
    {
        if (! $checkIn || ! $checkOut || $checkOut->lessThan($checkIn)) {
            return '';
        }

        $minutes = (int) $checkIn->diffInMinutes($checkOut);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
