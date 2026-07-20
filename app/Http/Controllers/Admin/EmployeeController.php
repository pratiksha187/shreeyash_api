<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\User;
use App\Models\DailyProgressReport;
use App\Support\PaidHolidayCalendar;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCompanyAdmin();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));

        $employeesQuery = User::query()
            ->forCurrentCompany()
            ->employees()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%')
                        ->orWhere('designation', 'like', '%'.$search.'%');

                    if (is_numeric($search)) {
                        $query->orWhere('id', (int) $search);
                    }
                });
            })
            ->latest();

        $employees = $employeesQuery
            ->paginate(10)
            ->appends($request->query());

        return view('admin.employees.index', [
            'employees' => $employees,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->ensureCompanyAdmin();

        return view('admin.employees.create', [
            'roles' => $this->roles(),
        ]);
    }

    public function show(Request $request, int $employee): View
    {
        $this->ensureCompanyAdmin();

        $employee = User::query()
            ->forCurrentCompany()
            ->employees()
            ->findOrFail($employee);

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $selectedMonth = $filters['month'] ?? now()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $attendances = $employee->attendances()
            ->whereBetween('attendance_date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->orderBy('attendance_date')
            ->get()
            ->keyBy(fn ($attendance) => $attendance->attendance_date->toDateString());

        $paidHolidays = PaidHolidayCalendar::holidaysBetween($monthStart, $monthEnd);

        $calendarDays = collect(CarbonPeriod::create($monthStart, $monthEnd))
            ->map(function (Carbon $date) use ($attendances, $paidHolidays, $employee) {
                $dateString = $date->toDateString();
                $attendance = $attendances->get($dateString);

                return [
                    'date' => $date->copy(),
                    'attendance' => $attendance,
                    'attendanceMeta' => $this->attendanceCalendarMeta($attendance, $employee),
                    'holiday' => $paidHolidays->get($dateString),
                ];
            });

        // Aggregate DPRs by date and build consolidated messages
        $dprGroups = DailyProgressReport::query()
            ->forCurrentCompany()
            ->where('user_id', $employee->id)
            ->whereBetween('dpr_date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->with(['hours.photos'])
            ->withCount(['hours', 'photos'])
            ->orderByDesc('dpr_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($r) => $r->dpr_date?->toDateString() ?? '')
            ->map(function ($reportsForDate, $date) use ($employee) {
                $allHours = $reportsForDate->flatMap(fn ($r) => $r->hours)->values();
                $photos = $allHours->flatMap(fn ($h) => $h->photos)->values();

                // Build consolidated message
                $dateLabel = Carbon::parse($date)->format('d M Y');
                $messageLines = [
                    'DAILY PROGRESS REPORT',
                    'Engineer: ' . $employee->name,
                    'Date: ' . $dateLabel,
                    'DPR Added: ' . $reportsForDate->count(),
                    '',
                ];

                foreach ($reportsForDate->sortBy('created_at')->values() as $index => $report) {
                    $messageLines[] = 'DPR #' . ($index + 1);
                    $messageLines[] = 'Site / Project: ' . $report->site_project;
                    $messageLines[] = 'Work Summary: ' . $report->work_summary;

                    foreach ($report->hours->sortBy('work_time')->values() as $hourIndex => $hour) {
                        $remark = trim((string) $hour->remark);
                        if ($remark === '') {
                            continue;
                        }

                        $time = Carbon::parse($hour->work_time)->format('h:i A');
                        $messageLines[] = ($hourIndex + 1) . '. ' . $time . ' - ' . preg_replace('/\r?\n+/', ' ', $remark);
                    }

                    $photoUrls = $report->hours
                        ->flatMap(fn ($hour) => $hour->photos)
                        ->map(fn ($photo) => $photo->publicUrl())
                        ->filter()
                        ->values();

                    if ($photoUrls->isNotEmpty()) {
                        $messageLines[] = 'Photos:';
                        foreach ($photoUrls as $photoUrl) {
                            $messageLines[] = $photoUrl;
                        }
                    }

                    $messageLines[] = '';
                }

                $message = trim(implode("\n", $messageLines));
                $whatsappUrl = $employee->mobile
                    ? 'https://web.whatsapp.com/send?phone='
                        . $this->whatsappPhone($employee->mobile)
                        . '&text='
                        . rawurlencode($message)
                    : null;

                return (object) [
                    'date' => $date,
                    'reports' => $reportsForDate,
                    'hours_count' => $allHours->count(),
                    'photos_count' => $photos->count(),
                    'photos' => $photos,
                    'message' => $message,
                    'whatsapp_url' => $whatsappUrl,
                ];
            })->values();

        $dprPage = LengthAwarePaginator::resolveCurrentPage();
        $dprs = new LengthAwarePaginator(
            $dprGroups->forPage($dprPage, 10)->values(),
            $dprGroups->count(),
            10,
            $dprPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.employees.show', [
            'employee' => $employee,
            'selectedMonth' => $selectedMonth,
            'monthLabel' => $monthStart->format('F Y'),
            'calendarDays' => $calendarDays,
            'blankDays' => $monthStart->dayOfWeek > 0
                ? range(1, $monthStart->dayOfWeek)
                : [],
            'trailingBlankDays' => $monthEnd->dayOfWeek < 6
                ? range(1, 6 - $monthEnd->dayOfWeek)
                : [],
            'summary' => [
                'total_days' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'leave' => $attendances->where('status', 'leave')->count(),
                'half_day' => $attendances->where('status', 'half_day')->count(),
                'paid_holidays' => $paidHolidays->count(),
            ],
            'dprs' => $dprs,
        ]);
    }

    public function edit(int $employee): View
    {
        $this->ensureCompanyAdmin();

        $employee = User::query()
            ->forCurrentCompany()
            ->employees()
            ->findOrFail($employee);

        return view('admin.employees.edit', [
            'employee' => $employee,
            'roles' => $this->roles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $usersTable = app(Tenant::class)->connectionName()
            ? app(Tenant::class)->connectionName().'.users'
            : 'users';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique($usersTable, 'email')],
            'mobile' => ['required', 'string', 'max:20', Rule::unique($usersTable, 'mobile')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'gender' => ['nullable', 'string', 'max:20'],
            'marital_status' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'join_date' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'probation_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'aadhaar_number' => ['nullable', 'string', 'max:20'],
            'hours_per_day' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'days_per_week' => ['nullable', 'integer', 'min:0', 'max:7'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'insurance' => ['nullable', 'numeric', 'min:0'],
            'pt' => ['nullable', 'numeric', 'min:0'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'pf' => ['nullable', 'numeric', 'min:0'],
            'designation' => ['nullable', 'string', 'max:255', Rule::exists('roles', 'name')],
        ]);

        $plainPassword = $data['password'];

        unset($data['password_confirmation']);
        $data['password'] = Hash::make($data['password']);
        $data['company_id'] = app(Tenant::class)->id();
        $data['role'] = 'employee';
        $data['is_active'] = true;

        $employee = User::query()->create($data);
        session()->put("employee_plain_passwords.{$employee->id}", $plainPassword);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employee added successfully. Click Open WhatsApp from the employee table to send credentials.');
    }

    public function update(Request $request, int $employee): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $employee = User::query()
            ->forCurrentCompany()
            ->employees()
            ->findOrFail($employee);

        $usersTable = app(Tenant::class)->connectionName()
            ? app(Tenant::class)->connectionName().'.users'
            : 'users';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique($usersTable, 'email')->ignore($employee->id)],
            'mobile' => ['required', 'string', 'max:20', Rule::unique($usersTable, 'mobile')->ignore($employee->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'gender' => ['nullable', 'string', 'max:20'],
            'marital_status' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'join_date' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'probation_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'aadhaar_number' => ['nullable', 'string', 'max:20'],
            'hours_per_day' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'days_per_week' => ['nullable', 'integer', 'min:0', 'max:7'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'insurance' => ['nullable', 'numeric', 'min:0'],
            'pt' => ['nullable', 'numeric', 'min:0'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'pf' => ['nullable', 'numeric', 'min:0'],
            'designation' => ['nullable', 'string', 'max:255', Rule::exists('roles', 'name')],
        ]);

        unset($data['password_confirmation']);

        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
            session()->put("employee_plain_passwords.{$employee->id}", $request->input('password'));
        } else {
            unset($data['password']);
        }

        $employee->update($data);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function sendCredentials(Request $request, int $employee): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $employee = User::query()
            ->forCurrentCompany()
            ->employees()
            ->findOrFail($employee);

        if (! $employee->mobile) {
            return back()->with('error', 'Employee mobile number is missing.');
        }

        $plainPassword = session()->pull("employee_plain_passwords.{$employee->id}");

        if ($plainPassword) {
            return $this->redirectToWhatsapp($employee, $plainPassword);
        }

        $plainPassword = Str::random(10);

        $employee->forceFill([
            'password' => Hash::make($plainPassword),
        ])->save();

        return $this->redirectToWhatsapp($employee, $plainPassword);
    }

    private function credentialMessage(User $employee, string $plainPassword): string
    {
        $company = app(Tenant::class)->company();

        return "Hello {$employee->name}, your ConstructKaro employee login details are:\n"
            ."Company: {$company?->name}\n"
            ."Company Code: {$company?->slug}\n"
            ."Mobile: {$employee->mobile}\n"
            ."Password: {$plainPassword}\n"
            .'Please login from your own mobile device only.';
    }

    private function whatsappPhone(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        return $digits;
    }

    private function redirectToWhatsapp(User $employee, string $plainPassword): RedirectResponse
    {
        return redirect()->away(
            'https://web.whatsapp.com/send?phone='
            .$this->whatsappPhone($employee->mobile)
            .'&text='
            .rawurlencode($this->credentialMessage($employee, $plainPassword))
        );
    }

    private function attendanceCalendarMeta(?Attendance $attendance, User $employee): array
    {
        if (! $attendance) {
            return [
                'label' => null,
                'class' => 'status-empty',
                'check_in' => null,
                'check_out' => null,
                'worked' => null,
                'note' => null,
            ];
        }

        $status = $attendance->status;
        $checkIn = $attendance->localCheckInAt();
        $checkOut = $attendance->localCheckOutAt();
        $workedMinutes = $this->workedMinutes($checkIn, $checkOut);
        $expectedMinutes = max(1, (int) round(((float) ($employee->hours_per_day ?: 9)) * 60));

        $meta = [
            'label' => str_replace('_', ' ', $status),
            'class' => 'status-' . $status,
            'check_in' => $checkIn?->format('h:i A'),
            'check_out' => $checkOut?->format('h:i A'),
            'worked' => $workedMinutes !== null ? $this->formatWorkedMinutes($workedMinutes) : null,
            'note' => null,
        ];

        if ($status !== 'present') {
            return $meta;
        }

        if (! $checkIn) {
            return $meta;
        }

        if (! $checkOut) {
            return [
                ...$meta,
                'label' => 'In progress',
                'class' => 'status-in_progress',
            ];
        }

        $graceTime = $checkIn->copy()->setTime(9, 20);
        $isLate = $checkIn->greaterThan($graceTime);
        $hasCompletedHours = $workedMinutes !== null && $workedMinutes >= $expectedMinutes;

        if ($isLate && $hasCompletedHours) {
            return [
                ...$meta,
                'label' => 'Completed hours',
                'class' => 'status-completed_hours',
            ];
        }

        if ($isLate) {
            return [
                ...$meta,
                'label' => 'Late / short hours',
                'class' => 'status-late_short',
                'note' => 'After 09:20',
            ];
        }

        if (! $hasCompletedHours) {
            return [
                ...$meta,
                'label' => 'Short hours',
                'class' => 'status-short_hours',
            ];
        }

        return $meta;
    }

    private function workedMinutes(?Carbon $checkIn, ?Carbon $checkOut): ?int
    {
        if (! $checkIn || ! $checkOut || $checkOut->lessThan($checkIn)) {
            return null;
        }

        return (int) $checkIn->diffInMinutes($checkOut);
    }

    private function formatWorkedMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d hrs', intdiv($minutes, 60), $minutes % 60);
    }

    private function ensureCompanyAdmin(): void
    {
        if (session()->has('admin_company_id') && app(Tenant::class)->hasCompany()) {
            return;
        }

        abort(403, 'Please login with an employer/company admin account to manage employees.');
    }

    private function roles()
    {
        return DB::table('roles')
            ->orderBy('name')
            ->pluck('name');
    }
}
