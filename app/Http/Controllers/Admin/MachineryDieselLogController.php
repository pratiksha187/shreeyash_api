<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\MachineryDieselLog;
use App\Models\Vehicle;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MachineryDieselLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
            'edit_id' => ['nullable', 'integer'],
        ]);

        $selectedDate = isset($filters['date'])
            ? Carbon::parse($filters['date'])->toDateString()
            : null;
        $selectedMonth = $filters['month'] ?? now()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $logs = MachineryDieselLog::query()
            ->forCurrentCompany()
            ->with(['site:id,name,address', 'engineer:id,name'])
            ->when($selectedDate, fn ($query) => $query->whereDate('issue_date', $selectedDate))
            ->when(! $selectedDate, fn ($query) => $query->whereBetween('issue_date', [$monthStart->toDateString(), $monthEnd->toDateString()]))
            ->orderByDesc('issue_date')
            ->orderBy('machinery')
            ->get();
        $editLog = isset($filters['edit_id'])
            ? MachineryDieselLog::query()
                ->forCurrentCompany()
                ->whereKey($filters['edit_id'])
                ->first()
            : null;

        return view('admin.machinery-diesel-logs.index', [
            'logs' => $logs,
            'editLog' => $editLog,
            'sites' => LabourSite::query()
                ->forCurrentCompany()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'vehicles' => Vehicle::query()
                ->forCurrentCompany()
                ->orderBy('vehicle_number')
                ->get(['id', 'vehicle_number', 'vehicle_type', 'driver_name']),
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'summary' => [
                'machinery_count' => $logs->pluck('machinery')->unique()->count(),
                'actual_issued' => $logs->sum(fn (MachineryDieselLog $log) => (float) $log->actual_diesel_issued_today_ltr),
                'expected_consumption' => $logs->sum(fn (MachineryDieselLog $log) => (float) $log->expected_consumption_ltr),
                'closing_balance' => $logs->sum(fn (MachineryDieselLog $log) => (float) $log->expected_closing_balance_ltr),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'log_id' => ['nullable', 'integer'],
            'issue_date' => ['required', 'date'],
            'labour_site_id' => ['nullable', Rule::exists($this->tenantTable('labour_sites'), 'id')],
            'machinery' => ['required', 'string', 'max:255'],
            'minimum_stock_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'daily_diesel_for_8hr_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'yesterday_balance_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'actual_diesel_issued_today_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'hours_worked' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'evening_physical_balance_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'remarks_auto' => ['nullable', 'boolean'],
        ]);

        $issueDate = Carbon::parse($data['issue_date'])->toDateString();
        $values = [
            'engineer_user_id' => session('admin_user_id'),
            'labour_site_id' => $data['labour_site_id'] ?? null,
            'minimum_stock_ltr' => $data['minimum_stock_ltr'] ?? 0,
            'daily_diesel_for_8hr_ltr' => $data['daily_diesel_for_8hr_ltr'] ?? 0,
            'yesterday_balance_ltr' => $data['yesterday_balance_ltr'] ?? 0,
            'actual_diesel_issued_today_ltr' => $data['actual_diesel_issued_today_ltr'] ?? 0,
            'hours_worked' => $data['hours_worked'] ?? 8,
            'evening_physical_balance_ltr' => $data['evening_physical_balance_ltr'] ?? null,
            'remarks' => ! empty($data['remarks_auto']) ? null : ($data['remarks'] ?? null),
        ];

        if (! empty($data['log_id'])) {
            $log = MachineryDieselLog::query()
                ->forCurrentCompany()
                ->whereKey($data['log_id'])
                ->firstOrFail();

            $log->fill(array_merge($values, [
                'issue_date' => $issueDate,
                'machinery' => $data['machinery'],
            ]))->save();
        } else {
            MachineryDieselLog::query()->updateOrCreate([
                'company_id' => app(Tenant::class)->id(),
                'issue_date' => $issueDate,
                'machinery' => $data['machinery'],
            ], $values);
        }

        return redirect()
            ->route('admin.machinery-diesel-logs.index', ['date' => $issueDate])
            ->with('success', 'Machinery diesel entry saved successfully.');
    }

    private function tenantTable(string $table): string
    {
        $connection = app(Tenant::class)->connectionName();

        return $connection ? $connection . '.' . $table : $table;
    }
}
