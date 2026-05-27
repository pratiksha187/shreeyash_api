<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::query()
            ->withCount('vehicleLogs')
            ->latest()
            ->paginate(10);

        return view('admin.vehicles.index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function create(): View
    {
        return view('admin.vehicles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $vehicle = Vehicle::query()->create($this->validateVehicle($request));

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('success', 'Vehicle added successfully.');
    }

    public function show(Request $request, Vehicle $vehicle): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'log_id' => ['nullable', 'integer'],
        ]);

        $selectedMonth = $filters['month'] ?? now()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $selectedVehicleLog = null;

        $vehicleLogs = $vehicle->vehicleLogs()
            ->whereBetween('entry_date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->orderBy('entry_date')
            ->orderBy('in_at')
            ->get();

        $logsByDate = $vehicleLogs->groupBy(fn ($vehicleLog) => $vehicleLog->entry_date->toDateString());

        if (! empty($filters['log_id'])) {
            $selectedVehicleLog = $vehicle->vehicleLogs()
                ->whereKey($filters['log_id'])
                ->first();
        }

        $calendarRows = $this->buildCalendarRows($monthStart, $monthEnd, $logsByDate);
        $totalKm = $calendarRows->sum('total_km');
        $dieselTotal = $calendarRows->sum('diesel_added');
        $totalOtMinutes = $calendarRows->sum('ot_minutes');
        $totalOtAmount = round(($totalOtMinutes / 60) * (float) $vehicle->ot_rate, 2);
        $totalBillingAmount = (float) $vehicle->fixed_monthly_amount + $totalOtAmount;
        $tdsAmount = round($totalBillingAmount * ((float) $vehicle->tds_percentage / 100), 2);
        $openingReadingRow = $calendarRows->firstWhere('has_start_reading', true);
        $closingReadingRow = $calendarRows->where('has_end_reading', true)->last();

        return view('admin.vehicles.show', [
            'vehicle' => $vehicle,
            'selectedMonth' => $selectedMonth,
            'selectedVehicleLog' => $selectedVehicleLog,
            'monthLabel' => $monthStart->format('F Y'),
            'calendarRows' => $calendarRows,
            'summary' => [
                'total_records' => $vehicleLogs->count(),
                'completed' => $vehicleLogs->whereNotNull('out_at')->count(),
                'inside' => $vehicleLogs->whereNull('out_at')->count(),
                'days_with_entries' => $logsByDate->count(),
                'total_km' => $totalKm,
                'total_ot_minutes' => $totalOtMinutes,
            ],
            'billingSummary' => [
                'opening_reading' => $openingReadingRow ? $openingReadingRow['start_reading'] : 0,
                'closing_reading' => $closingReadingRow ? $closingReadingRow['end_reading'] : 0,
                'total_km' => $totalKm,
                'diesel_total' => $dieselTotal,
                'average' => $dieselTotal > 0 ? $totalKm / $dieselTotal : 0,
                'fixed_monthly_amount' => (float) $vehicle->fixed_monthly_amount,
                'ot_minutes' => $totalOtMinutes,
                'ot_hours' => $this->formatMinutes($totalOtMinutes),
                'ot_rate' => (float) $vehicle->ot_rate,
                'total_ot_amount' => $totalOtAmount,
                'total_billing_amount' => $totalBillingAmount,
                'tds_percentage' => (float) $vehicle->tds_percentage,
                'tds_amount' => $tdsAmount,
                'net_payable' => $totalBillingAmount - $tdsAmount,
            ],
        ]);
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles.edit', [
            'vehicle' => $vehicle,
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->validateVehicle($request, $vehicle));

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('success', 'Vehicle details updated successfully.');
    }

    private function buildCalendarRows(Carbon $monthStart, Carbon $monthEnd, $logsByDate)
    {
        return collect(CarbonPeriod::create($monthStart, $monthEnd))
            ->values()
            ->map(function (Carbon $date, int $index) use ($logsByDate) {
                $dateString = $date->toDateString();
                $logs = $logsByDate->get($dateString, collect())->values();
                $firstLog = $logs->first();
                $lastOutLog = $logs
                    ->filter(fn ($log) => $log->out_at)
                    ->sortBy('out_at')
                    ->last();
                $startReadingLog = $logs->first(fn ($log) => (float) $log->start_reading > 0);
                $endReadingLog = $logs->reverse()->first(fn ($log) => (float) $log->end_reading > 0);
                $startReading = $startReadingLog ? (float) $startReadingLog->start_reading : 0;
                $endReading = $endReadingLog ? (float) $endReadingLog->end_reading : 0;
                $totalKm = $startReading > 0 && $endReading >= $startReading ? $endReading - $startReading : 0;
                $totalMinutes = (int) $logs->sum(function ($log) {
                    if (! $log->in_at || ! $log->out_at) {
                        return 0;
                    }

                    return $this->minutesForLog($log);
                });
                $parsedOtMinutes = (int) $logs->sum(fn ($log) => $this->minutesFromRemarks($log->remarks, 'OT Hrs'));
                $otMinutes = $parsedOtMinutes > 0 ? $parsedOtMinutes : max(0, $totalMinutes - 720);
                $remarks = $logs
                    ->flatMap(fn ($log) => [$log->purpose, $log->remarks])
                    ->filter()
                    ->unique()
                    ->implode(', ');
                $inAt = $this->displayInAt($firstLog, $totalMinutes);

                return [
                    'sr_no' => $index + 1,
                    'log_id' => $firstLog?->id,
                    'date' => $date->copy(),
                    'day' => $date->format('D'),
                    'challan_no' => $logs->pluck('challan_no')->filter()->unique()->implode(', '),
                    'diesel_added' => (float) $logs->sum(fn ($log) => (float) $log->diesel_added),
                    'start_reading' => $startReading,
                    'end_reading' => $endReading,
                    'has_start_reading' => (bool) $startReadingLog,
                    'has_end_reading' => (bool) $endReadingLog,
                    'total_km' => $totalKm,
                    'in_at' => $inAt,
                    'out_at' => $lastOutLog?->out_at,
                    'in_time_value' => $inAt?->format('H:i'),
                    'out_time_value' => $lastOutLog?->out_at?->format('H:i'),
                    'total_minutes' => $totalMinutes,
                    'total_hours' => $this->formatMinutes($totalMinutes),
                    'ot_minutes' => $otMinutes,
                    'ot_hours' => $this->formatMinutes($otMinutes),
                    'remarks' => $remarks,
                    'entry_remarks' => $firstLog?->remarks,
                    'edit_log_id' => $firstLog?->id,
                ];
            });
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function minutesForLog($log): int
    {
        if ($log->in_at && $log->out_at && $log->entry_date && $log->in_at->isSameDay($log->entry_date)) {
            return max(0, (int) $log->in_at->diffInMinutes($log->out_at));
        }

        return $this->minutesFromRemarks($log->remarks, 'Total Hrs');
    }

    private function displayInAt($log, int $totalMinutes): ?Carbon
    {
        if (! $log) {
            return null;
        }

        if ($log->in_at && $log->entry_date && $log->in_at->isSameDay($log->entry_date)) {
            return $log->in_at;
        }

        if ($log->out_at && $totalMinutes > 0) {
            return $log->out_at->copy()->subMinutes($totalMinutes);
        }

        return null;
    }

    private function minutesFromRemarks(?string $remarks, string $label): int
    {
        if (! $remarks || ! preg_match('/'.preg_quote($label, '/').':\s*(\d{1,3}):(\d{2})/i', $remarks, $matches)) {
            return 0;
        }

        return ((int) $matches[1] * 60) + (int) $matches[2];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVehicle(Request $request, ?Vehicle $vehicle = null): array
    {
        $vehicleNumberRule = Rule::unique('vehicles', 'vehicle_number');

        if ($vehicle) {
            $vehicleNumberRule->ignore($vehicle->id);
        }

        $data = $request->validate([
            'vehicle_number' => [
                'required',
                'string',
                'max:50',
                $vehicleNumberRule,
            ],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_mobile' => ['nullable', 'string', 'max:20'],
            'fixed_monthly_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'ot_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'tds_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $data['vehicle_number'] = strtoupper($data['vehicle_number']);
        $data['fixed_monthly_amount'] = $data['fixed_monthly_amount'] ?? 0;
        $data['ot_rate'] = $data['ot_rate'] ?? 0;
        $data['tds_percentage'] = $data['tds_percentage'] ?? 1;

        return $data;
    }
}
