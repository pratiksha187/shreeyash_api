<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleLogController extends Controller
{
    public function storeMonthly(Request $request, int $vehicle): RedirectResponse
    {
        $vehicle = $this->findVehicle($vehicle);

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'entries' => ['required', 'array'],
            'entries.*.log_id' => ['nullable', 'integer'],
            'entries.*.entry_date' => ['required', 'date'],
            'entries.*.challan_no' => ['nullable', 'string', 'max:100'],
            'entries.*.site_name' => ['nullable', 'string', 'max:255'],
            'entries.*.diesel_added' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'entries.*.start_reading' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'entries.*.end_reading' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'entries.*.in_time' => ['nullable', 'date_format:H:i'],
            'entries.*.out_time' => ['nullable', 'date_format:H:i'],
            'entries.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($data['entries'] as $entry) {
            $this->saveMonthlyEntry($vehicle, $entry);
        }

        return redirect()
            ->route('admin.vehicles.show', [
                'vehicle' => $vehicle,
                'month' => $data['month'],
            ])
            ->with('success', 'Monthly vehicle entries saved successfully.');
    }

    public function store(Request $request, int $vehicle): RedirectResponse
    {
        $vehicle = $this->findVehicle($vehicle);

        $data = $this->validateVehicleLog($request);
        $data = $this->prepareVehicleLogData($vehicle, $data);

        $vehicleLog = $vehicle->vehicleLogs()->create($data);

        return redirect()
            ->route('admin.vehicles.show', [
                'vehicle' => $vehicle,
                'month' => Carbon::parse($data['entry_date'])->format('Y-m'),
                'log_id' => $vehicleLog->id,
            ])
            ->with('success', 'Vehicle day entry saved successfully.');
    }

    public function edit(int $vehicle, int $vehicleLog): View
    {
        $vehicle = $this->findVehicle($vehicle);
        $vehicleLog = $this->findVehicleLog($vehicle, $vehicleLog);

        return view('admin.vehicles.log-edit', [
            'vehicle' => $vehicle,
            'vehicleLog' => $vehicleLog,
        ]);
    }

    public function update(Request $request, int $vehicle, int $vehicleLog): RedirectResponse
    {
        $vehicle = $this->findVehicle($vehicle);
        $vehicleLog = $this->findVehicleLog($vehicle, $vehicleLog);

        $data = $this->prepareVehicleLogData($vehicle, $this->validateVehicleLog($request));
        $vehicleLog->update($data);

        return redirect()
            ->route('admin.vehicles.show', [
                'vehicle' => $vehicle,
                'month' => Carbon::parse($data['entry_date'])->format('Y-m'),
                'log_id' => $vehicleLog->id,
            ])
            ->with('success', 'Vehicle day entry updated successfully.');
    }

    private function findVehicle(int $vehicle): Vehicle
    {
        return Vehicle::query()
            ->forCurrentCompany()
            ->whereKey($vehicle)
            ->firstOrFail();
    }

    private function findVehicleLog(Vehicle $vehicle, int $vehicleLog): VehicleLog
    {
        return $vehicle->vehicleLogs()
            ->whereKey($vehicleLog)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVehicleLog(Request $request): array
    {
        return $request->validate([
            'entry_date' => ['required', 'date'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_mobile' => ['nullable', 'string', 'max:20'],
            'challan_no' => ['nullable', 'string', 'max:100'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'diesel_added' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'start_reading' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'end_reading' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'in_at' => ['required', 'date'],
            'out_at' => ['nullable', 'date'],
            'purpose' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareVehicleLogData(Vehicle $vehicle, array $data): array
    {
        $data['vehicle_id'] = $vehicle->id;
        $data['entry_date'] = Carbon::parse($data['entry_date'])->toDateString();
        $data['vehicle_number'] = $vehicle->vehicle_number;
        $data['vehicle_type'] = $vehicle->vehicle_type;
        $data['driver_name'] = $data['driver_name'] ?: $vehicle->driver_name;
        $data['driver_mobile'] = $data['driver_mobile'] ?: $vehicle->driver_mobile;
        $data['site_name'] = ($data['site_name'] ?? null) ?: $vehicle->default_site;
        $data['diesel_added'] = $data['diesel_added'] ?? 0;
        $data['start_reading'] = $data['start_reading'] ?? 0;
        $data['end_reading'] = $data['end_reading'] ?? 0;

        return $data;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function saveMonthlyEntry(Vehicle $vehicle, array $entry): void
    {
        $entryDate = Carbon::parse($entry['entry_date'])->toDateString();
        $vehicleLog = null;

        if (! empty($entry['log_id'])) {
            $vehicleLog = $vehicle->vehicleLogs()
                ->whereKey($entry['log_id'])
                ->first();
        }

        $vehicleLog ??= $vehicle->vehicleLogs()
            ->whereDate('entry_date', $entryDate)
            ->orderBy('id')
            ->first();

        if (! $vehicleLog && ! $this->monthlyEntryHasData($entry)) {
            return;
        }

        $inAt = $this->dateTimeFromEntryTime($entryDate, $entry['in_time'] ?? null);
        $outAt = $this->dateTimeFromEntryTime($entryDate, $entry['out_time'] ?? null);

        if ($inAt && $outAt && $outAt->lessThan($inAt)) {
            $outAt->addDay();
        }

        if (! $inAt && ! $vehicleLog) {
            $inAt = Carbon::parse($entryDate)->startOfDay();
        }

        $payload = [
            'entry_date' => $entryDate,
            'vehicle_number' => $vehicle->vehicle_number,
            'vehicle_type' => $vehicle->vehicle_type,
            'driver_name' => $vehicle->driver_name,
            'driver_mobile' => $vehicle->driver_mobile,
            'challan_no' => $entry['challan_no'] ?? null,
            'site_name' => filled($entry['site_name'] ?? null) ? $entry['site_name'] : $vehicle->default_site,
            'diesel_added' => $entry['diesel_added'] ?? 0,
            'start_reading' => $entry['start_reading'] ?? 0,
            'end_reading' => $entry['end_reading'] ?? 0,
            'out_at' => $outAt,
            'purpose' => null,
            'remarks' => $entry['remarks'] ?? null,
        ];

        if ($inAt) {
            $payload['in_at'] = $inAt;
        }

        $vehicleLog
            ? $vehicleLog->update($payload)
            : $vehicle->vehicleLogs()->create($payload);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function monthlyEntryHasData(array $entry): bool
    {
        foreach (['challan_no', 'site_name', 'in_time', 'out_time', 'remarks'] as $field) {
            if (filled($entry[$field] ?? null)) {
                return true;
            }
        }

        foreach (['diesel_added', 'start_reading', 'end_reading'] as $field) {
            if ((float) ($entry[$field] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function dateTimeFromEntryTime(string $entryDate, ?string $time): ?Carbon
    {
        if (! filled($time)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i', $entryDate.' '.$time);
    }
}
