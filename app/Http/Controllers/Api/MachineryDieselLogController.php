<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\MachineryDieselLog;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MachineryDieselLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->hasTable()) {
            return response()->json([
                'message' => 'Machinery diesel log table is not available yet.',
                'machinery_diesel_logs' => [],
            ], 503);
        }

        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'date' => ['nullable', 'date'],
            'machinery' => ['nullable', 'string', 'max:255'],
            'labour_site_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $logs = MachineryDieselLog::query()
            ->forCurrentCompany()
            ->with(['site:id,name,address', 'engineer:id,name,mobile,designation'])
            ->when(isset($filters['date']), fn ($query) => $query->whereDate('issue_date', Carbon::parse($filters['date'])->toDateString()))
            ->when(isset($filters['from_date']), fn ($query) => $query->whereDate('issue_date', '>=', Carbon::parse($filters['from_date'])->toDateString()))
            ->when(isset($filters['to_date']), fn ($query) => $query->whereDate('issue_date', '<=', Carbon::parse($filters['to_date'])->toDateString()))
            ->when(isset($filters['machinery']), fn ($query) => $query->where('machinery', 'like', '%' . $filters['machinery'] . '%'))
            ->when(isset($filters['labour_site_id']), fn ($query) => $query->where('labour_site_id', $filters['labour_site_id']))
            ->orderByDesc('issue_date')
            ->orderBy('machinery')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->map(fn (MachineryDieselLog $log) => $this->payload($log));

        return response()->json([
            'message' => 'Machinery diesel logs fetched successfully.',
            'machinery_diesel_logs' => $logs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->hasTable()) {
            return response()->json([
                'message' => 'Machinery diesel log table is not available yet.',
            ], 503);
        }

        $this->normalizeInput($request);

        $data = $request->validate([
            'entries' => ['nullable', 'array', 'min:1', 'max:100'],
            'entries.*' => ['required', 'array'],
            'issue_date' => ['required_without:entries', 'date'],
            'date' => ['nullable', 'date'],
            'labour_site_id' => ['nullable', Rule::exists($this->tenantTable('labour_sites'), 'id')],
            'machinery' => ['required_without:entries', 'string', 'max:255'],
            'minimum_stock_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'daily_diesel_for_8hr_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'yesterday_balance_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'actual_diesel_issued_today_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'hours_worked' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'evening_physical_balance_ltr' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $entries = isset($data['entries']) ? $data['entries'] : [$data];
        $logs = collect();
        $created = false;

        foreach ($entries as $entry) {
            $entry = $this->normalizeEntry($entry, $request);
            $validated = validator($entry, $this->entryRules())->validate();
            $issueDate = Carbon::parse($validated['issue_date'])->toDateString();
            $yesterdayBalance = array_key_exists('yesterday_balance_ltr', $validated)
                ? $validated['yesterday_balance_ltr']
                : $this->previousBalance($validated['machinery'], $issueDate);

            $log = MachineryDieselLog::query()->updateOrCreate(
                [
                    'company_id' => $request->user()->company_id,
                    'issue_date' => $issueDate,
                    'machinery' => $validated['machinery'],
                ],
                [
                    'engineer_user_id' => $request->user()->id,
                    'labour_site_id' => $validated['labour_site_id'] ?? null,
                    'minimum_stock_ltr' => $validated['minimum_stock_ltr'] ?? 0,
                    'daily_diesel_for_8hr_ltr' => $validated['daily_diesel_for_8hr_ltr'] ?? 0,
                    'yesterday_balance_ltr' => $yesterdayBalance,
                    'actual_diesel_issued_today_ltr' => $validated['actual_diesel_issued_today_ltr'] ?? 0,
                    'hours_worked' => $validated['hours_worked'] ?? 8,
                    'evening_physical_balance_ltr' => $validated['evening_physical_balance_ltr'] ?? null,
                    'remarks' => $validated['remarks'] ?? null,
                ]
            );

            $created = $created || $log->wasRecentlyCreated;
            $log->load(['site:id,name,address', 'engineer:id,name,mobile,designation']);
            $logs->push($log);
        }

        $payloads = $logs->map(fn (MachineryDieselLog $log) => $this->payload($log))->values();
        $response = [
            'message' => $created ? 'Machinery diesel log saved successfully.' : 'Machinery diesel log updated successfully.',
            'machinery_diesel_logs' => $payloads,
        ];

        if ($payloads->count() === 1) {
            $response['machinery_diesel_log'] = $payloads->first();
        }

        return response()->json($response, $created ? 201 : 200);
    }

    public function show(int $machineryDieselLog): JsonResponse
    {
        if (! $this->hasTable()) {
            return response()->json([
                'message' => 'Machinery diesel log table is not available yet.',
            ], 503);
        }

        $log = MachineryDieselLog::query()
            ->forCurrentCompany()
            ->with(['site:id,name,address', 'engineer:id,name,mobile,designation'])
            ->findOrFail($machineryDieselLog);

        return response()->json([
            'message' => 'Machinery diesel log fetched successfully.',
            'machinery_diesel_log' => $this->payload($log),
        ]);
    }

    private function normalizeInput(Request $request): void
    {
        if ($request->has('entries')) {
            return;
        }

        $request->merge($this->normalizeEntry($request->all(), $request));
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function normalizeEntry(array $entry, Request $request): array
    {
        $aliases = [
            'issue_date' => ['date', 'issueDate', 'entry_date', 'entryDate'],
            'labour_site_id' => ['site_id', 'siteId', 'labourSiteId'],
            'minimum_stock_ltr' => ['minimum_stock', 'minimumStock', 'minimum_stock_l', 'minimumStockL'],
            'daily_diesel_for_8hr_ltr' => ['daily_diesel_for_8hr', 'dailyDieselFor8Hr', 'daily_diesel_for_8_hr_ltr', 'dailyDiesel'],
            'yesterday_balance_ltr' => ['yesterday_balance', 'yesterdayBalance', 'opening_balance', 'openingBalance'],
            'actual_diesel_issued_today_ltr' => ['actual_diesel_issued_today', 'actualDieselIssuedToday', 'actual_issued', 'actualIssued'],
            'hours_worked' => ['hoursWorked', 'hours'],
            'evening_physical_balance_ltr' => ['evening_physical_balance', 'eveningPhysicalBalance', 'physical_balance', 'physicalBalance'],
            'remarks' => ['remark', 'note', 'notes'],
        ];

        foreach ($aliases as $target => $keys) {
            if (array_key_exists($target, $entry)) {
                continue;
            }

            foreach ($keys as $key) {
                if (array_key_exists($key, $entry)) {
                    $entry[$target] = $entry[$key];
                    break;
                }
            }
        }

        if (! array_key_exists('labour_site_id', $entry) && $request->has('labour_site_id')) {
            $entry['labour_site_id'] = $request->input('labour_site_id');
        }

        if (! array_key_exists('issue_date', $entry) && $request->has('issue_date')) {
            $entry['issue_date'] = $request->input('issue_date');
        }

        return $entry;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function entryRules(): array
    {
        return [
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
        ];
    }

    private function previousBalance(string $machinery, string $issueDate): float
    {
        $previousLog = MachineryDieselLog::query()
            ->forCurrentCompany()
            ->whereRaw('LOWER(machinery) = ?', [strtolower($machinery)])
            ->whereDate('issue_date', '<', $issueDate)
            ->orderByDesc('issue_date')
            ->first();

        if (! $previousLog) {
            return 0;
        }

        return (float) ($previousLog->evening_physical_balance_ltr ?? $previousLog->expected_closing_balance_ltr ?? 0);
    }

    private function payload(MachineryDieselLog $log): array
    {
        return [
            'id' => $log->id,
            'issue_date' => $log->issue_date?->toDateString(),
            'date_display' => $log->issue_date?->format('d M Y'),
            'machinery' => $log->machinery,
            'minimum_stock_ltr' => $log->minimum_stock_ltr,
            'daily_diesel_for_8hr_ltr' => $log->daily_diesel_for_8hr_ltr,
            'yesterday_balance_ltr' => $log->yesterday_balance_ltr,
            'diesel_to_issue_today_ltr' => $log->diesel_to_issue_today_ltr,
            'actual_diesel_issued_today_ltr' => $log->actual_diesel_issued_today_ltr,
            'extra_diesel_issued_ltr' => $log->extra_diesel_issued_ltr,
            'total_diesel_available_after_filling_ltr' => $log->total_diesel_available_after_filling_ltr,
            'hours_worked' => $log->hours_worked,
            'expected_consumption_ltr' => $log->expected_consumption_ltr,
            'expected_closing_balance_ltr' => $log->expected_closing_balance_ltr,
            'evening_physical_balance_ltr' => $log->evening_physical_balance_ltr,
            'difference_ltr' => $log->difference_ltr,
            'diesel_to_issue_tomorrow_ltr' => $log->diesel_to_issue_tomorrow_ltr,
            'remarks' => $log->remarks,
            'site' => $log->site ? [
                'id' => $log->site->id,
                'name' => $log->site->name,
                'address' => $log->site->address,
            ] : null,
            'engineer' => $log->engineer ? [
                'id' => $log->engineer->id,
                'name' => $log->engineer->name,
                'mobile' => $log->engineer->mobile,
                'designation' => $log->engineer->designation,
            ] : null,
            'submitted_at' => $log->created_at,
            'updated_at' => $log->updated_at,
        ];
    }

    private function tenantTable(string $table): string
    {
        $connection = app(Tenant::class)->connectionName();

        return $connection ? $connection . '.' . $table : $table;
    }

    private function hasTable(): bool
    {
        return DB::connection(app(Tenant::class)->connectionName())
            ->getSchemaBuilder()
            ->hasTable('machinery_diesel_logs');
    }
}
