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
            ->forCurrentCompany()
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

    public function show(Request $request, int $vehicle): View
    {
        $vehicle = $this->findVehicle($vehicle);
        $isCamper = $this->isCamper($vehicle);

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'log_id' => ['nullable', 'integer'],
        ]);

        $selectedMonth = $filters['month'] ?? now()->format('Y-m');
        $monthStart = $this->billingCycleStartDate($selectedMonth, $vehicle);
        $monthEnd = $monthStart->copy()->addMonthNoOverflow()->subDay();
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

        $calendarRows = $this->buildCalendarRows($vehicle, $monthStart, $monthEnd, $logsByDate);
        $totalKm = $calendarRows->sum('total_km');
        $dieselTotal = $calendarRows->sum('diesel_added');
        $totalDutyMinutes = $calendarRows->sum('total_minutes');
        $totalHireHours = $calendarRows->sum('hire_hours');
        $hireTotalAmount = $calendarRows->sum('hire_amount');
        $totalOtMinutes = $calendarRows->sum('ot_minutes');
        $camperKmLimit = $isCamper ? $this->camperBillingValue($vehicle, 'KM Limit', 3500) : 0;
        $extraKm = $isCamper ? max(0, $totalKm - $camperKmLimit) : 0;
        $extraKmRate = $isCamper ? $this->camperBillingValue($vehicle, 'Extra KM Rate', 5) : 0;
        $extraKmAmount = round($extraKm * $extraKmRate, 2);
        $effectiveOtRate = $isCamper ? $this->camperBillingValue($vehicle, 'Company OT Rate', 55) : (float) $vehicle->ot_rate;
        $totalOtAmount = round(($totalOtMinutes / 60) * $effectiveOtRate, 2);
        $baseBillingAmount = $isCamper
            ? (float) $vehicle->fixed_monthly_amount
            : $hireTotalAmount;
        $grossBillingAmount = $baseBillingAmount + (float) $vehicle->extra_sunday_paid_amount + $totalOtAmount + $extraKmAmount;
        $gstAmount = round($grossBillingAmount * ((float) $vehicle->gst_percentage / 100), 2);
        $totalBillingAmount = $grossBillingAmount + $gstAmount;
        $tdsAmount = round($totalBillingAmount * ((float) $vehicle->tds_percentage / 100), 2);
        $openingReadingRow = $calendarRows->firstWhere('has_start_reading', true);
        $closingReadingRow = $calendarRows->where('has_end_reading', true)->last();

        return view('admin.vehicles.show', [
            'vehicle' => $vehicle,
            'isCamper' => $isCamper,
            'selectedMonth' => $selectedMonth,
            'selectedVehicleLog' => $selectedVehicleLog,
            'monthLabel' => $this->billingCycleLabel($monthStart, $monthEnd),
            'calendarRows' => $calendarRows,
            'summary' => [
                'total_records' => $vehicleLogs->count(),
                'completed' => $vehicleLogs->whereNotNull('out_at')->count(),
                'inside' => $vehicleLogs->whereNull('out_at')->count(),
                'days_with_entries' => $logsByDate->count(),
                'total_km' => $totalKm,
                'total_duty_minutes' => $totalDutyMinutes,
                'total_duty_hours' => $this->formatMinutes($totalDutyMinutes),
                'total_hire_hours' => $totalHireHours,
                'hire_total_amount' => $hireTotalAmount,
                'total_ot_minutes' => $totalOtMinutes,
            ],
            'billingSummary' => [
                'opening_reading' => $openingReadingRow ? $openingReadingRow['start_reading'] : 0,
                'closing_reading' => $closingReadingRow ? $closingReadingRow['end_reading'] : 0,
                'total_km' => $totalKm,
                'km_limit' => $camperKmLimit,
                'extra_km' => $extraKm,
                'extra_km_rate' => $extraKmRate,
                'extra_km_amount' => $extraKmAmount,
                'diesel_total' => $dieselTotal,
                'average' => $dieselTotal > 0 ? $totalKm / $dieselTotal : 0,
                'fixed_monthly_amount' => (float) $vehicle->fixed_monthly_amount,
                'base_billing_amount' => $baseBillingAmount,
                'hire_per_day_rate' => (float) $vehicle->hire_per_day_rate,
                'hire_per_hour_rate' => (float) $vehicle->hire_per_hour_rate,
                'total_duty_hours' => $this->formatMinutes($totalDutyMinutes),
                'total_hire_hours' => $totalHireHours,
                'hire_total_amount' => $hireTotalAmount,
                'extra_sunday_paid_amount' => (float) $vehicle->extra_sunday_paid_amount,
                'ot_minutes' => $totalOtMinutes,
                'ot_hours' => $this->formatMinutes($totalOtMinutes),
                'ot_rate' => $effectiveOtRate,
                'total_ot_amount' => $totalOtAmount,
                'gross_billing_amount' => $grossBillingAmount,
                'gst_percentage' => (float) $vehicle->gst_percentage,
                'gst_amount' => $gstAmount,
                'total_billing_amount' => $totalBillingAmount,
                'tds_percentage' => (float) $vehicle->tds_percentage,
                'tds_amount' => $tdsAmount,
                'net_payable' => $totalBillingAmount - $tdsAmount,
            ],
        ]);
    }

    public function edit(int $vehicle): View
    {
        $vehicle = $this->findVehicle($vehicle);

        return view('admin.vehicles.edit', [
            'vehicle' => $vehicle,
        ]);
    }

    public function update(Request $request, int $vehicle): RedirectResponse
    {
        $vehicle = $this->findVehicle($vehicle);

        $vehicle->update($this->validateVehicle($request, $vehicle));

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('success', 'Vehicle details updated successfully.');
    }

    private function findVehicle(int $vehicle): Vehicle
    {
        return Vehicle::query()
            ->forCurrentCompany()
            ->whereKey($vehicle)
            ->firstOrFail();
    }

    private function buildCalendarRows(Vehicle $vehicle, Carbon $monthStart, Carbon $monthEnd, $logsByDate)
    {
        return collect(CarbonPeriod::create($monthStart, $monthEnd))
            ->values()
            ->map(function (Carbon $date, int $index) use ($logsByDate, $vehicle) {
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
                $totalMinutes = (int) $logs->sum(fn ($log) => $this->minutesForVehicleLog($vehicle, $log));
                $hireHours = round($totalMinutes / 60, 2);
                $hireAmount = round($hireHours * (float) $vehicle->hire_per_hour_rate, 2);
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
                    'site_name' => $logs->pluck('site_name')->filter()->unique()->implode(', '),
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
                    'first_half_in_value' => $this->sessionTimeFromRemarks($firstLog?->remarks, 'First Half In') ?? $firstLog?->in_at?->format('H:i'),
                    'first_half_out_value' => $this->sessionTimeFromRemarks($firstLog?->remarks, 'First Half Out'),
                    'second_half_in_value' => $this->sessionTimeFromRemarks($firstLog?->remarks, 'Second Half In'),
                    'second_half_out_value' => $this->sessionTimeFromRemarks($firstLog?->remarks, 'Second Half Out') ?? $lastOutLog?->out_at?->format('H:i'),
                    'total_minutes' => $totalMinutes,
                    'total_hours' => $this->formatMinutes($totalMinutes),
                    'hire_hours' => $hireHours,
                    'hire_amount' => $hireAmount,
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

    private function minutesForVehicleLog(Vehicle $vehicle, $log): int
    {
        if ($this->isCamper($vehicle)) {
            if (! $log->in_at || ! $log->out_at) {
                return 0;
            }

            return $this->minutesForLog($log);
        }

        $firstHalf = $this->sessionMinutesFromRemarks($log->remarks, 'First Half');
        $secondHalf = $this->sessionMinutesFromRemarks($log->remarks, 'Second Half');

        if ($firstHalf > 0 || $secondHalf > 0) {
            return $firstHalf + $secondHalf;
        }

        if ($log->in_at && $log->out_at) {
            return $this->minutesForLog($log);
        }

        return 0;
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

    private function sessionTimeFromRemarks(?string $remarks, string $label): ?string
    {
        if (! $remarks || ! preg_match('/'.preg_quote($label, '/').':\s*(\d{2}:\d{2})/i', $remarks, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function sessionMinutesFromRemarks(?string $remarks, string $label): int
    {
        $start = $this->sessionTimeFromRemarks($remarks, $label.' In');
        $end = $this->sessionTimeFromRemarks($remarks, $label.' Out');

        if (! $start || ! $end) {
            return 0;
        }

        [$startHour, $startMinute] = array_map('intval', explode(':', $start));
        [$endHour, $endMinute] = array_map('intval', explode(':', $end));
        $startMinutes = ($startHour * 60) + $startMinute;
        $endMinutes = ($endHour * 60) + $endMinute;

        if ($endMinutes < $startMinutes) {
            $endMinutes += 1440;
        }

        return max(0, $endMinutes - $startMinutes);
    }

    private function isCamper(Vehicle $vehicle): bool
    {
        return str_contains(strtolower((string) $vehicle->vehicle_type), 'camper');
    }

    private function camperBillingValue(Vehicle $vehicle, string $label, float $default): float
    {
        if (
            $vehicle->remarks
            && preg_match('/'.preg_quote($label, '/').':\s*([0-9]+(?:\.[0-9]+)?)/i', $vehicle->remarks, $matches)
        ) {
            return (float) $matches[1];
        }

        return $default;
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
            'default_site' => ['nullable', 'string', 'max:255'],
            'billing_cycle_start_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'fixed_monthly_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'ot_rate' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'hire_per_day_rate' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'hire_per_hour_rate' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'tds_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'gst_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'extra_sunday_paid_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $data['vehicle_number'] = strtoupper($data['vehicle_number']);
        $data['billing_cycle_start_day'] = $data['billing_cycle_start_day'] ?? 1;
        $data['fixed_monthly_amount'] = $data['fixed_monthly_amount'] ?? 0;
        $data['ot_rate'] = $data['ot_rate'] ?? 0;
        $data['hire_per_day_rate'] = $data['hire_per_day_rate'] ?? 0;
        $data['hire_per_hour_rate'] = $data['hire_per_hour_rate'] ?? 0;
        $data['tds_percentage'] = $data['tds_percentage'] ?? 1;
        $data['gst_percentage'] = $data['gst_percentage'] ?? 18;
        $data['extra_sunday_paid_amount'] = $data['extra_sunday_paid_amount'] ?? 0;

        return $data;
    }

    private function billingCycleStartDate(string $selectedMonth, Vehicle $vehicle): Carbon
    {
        $month = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $startDay = min((int) ($vehicle->billing_cycle_start_day ?: 1), $month->daysInMonth);

        return $month->day($startDay)->startOfDay();
    }

    private function billingCycleLabel(Carbon $startDate, Carbon $endDate): string
    {
        if ($startDate->isSameMonth($endDate)) {
            return $startDate->format('F Y');
        }

        return $startDate->format('d M Y').' to '.$endDate->format('d M Y');
    }
}
