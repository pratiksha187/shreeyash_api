<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $this->ensureCompanyAdmin();

        $employees = User::query()
            ->forCurrentCompany()
            ->employees()
            ->latest()
            ->paginate(10);

        return view('admin.employees.index', [
            'employees' => $employees,
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

        $calendarDays = collect(CarbonPeriod::create($monthStart, $monthEnd))
            ->map(function (Carbon $date) use ($attendances) {
                $dateString = $date->toDateString();

                return [
                    'date' => $date->copy(),
                    'attendance' => $attendances->get($dateString),
                ];
            });

        return view('admin.employees.show', [
            'employee' => $employee,
            'selectedMonth' => $selectedMonth,
            'monthLabel' => $monthStart->format('F Y'),
            'calendarDays' => $calendarDays,
            'blankDays' => $monthStart->dayOfWeek > 0
                ? range(1, $monthStart->dayOfWeek)
                : [],
            'summary' => [
                'total_days' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'leave' => $attendances->where('status', 'leave')->count(),
                'half_day' => $attendances->where('status', 'half_day')->count(),
            ],
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
