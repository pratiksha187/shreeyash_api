<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceRecord;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleMaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'vehicle_id' => ['nullable', 'integer'],
            'record_type' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:40'],
        ]);

        $records = VehicleMaintenanceRecord::query()
            ->forCurrentCompany()
            ->with('vehicle')
            ->when($filters['vehicle_id'] ?? null, fn ($query, $vehicleId) => $query->where('vehicle_id', $vehicleId))
            ->when($filters['record_type'] ?? null, fn ($query, $type) => $query->where('record_type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('record_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summaryQuery = VehicleMaintenanceRecord::query()->forCurrentCompany();

        return view('admin.vehicles.maintenance', [
            'records' => $records,
            'vehicles' => Vehicle::query()->forCurrentCompany()->orderBy('vehicle_number')->get(['id', 'vehicle_number', 'vehicle_type']),
            'filters' => $filters,
            'summary' => [
                'total_records' => (clone $summaryQuery)->count(),
                'open_jobs' => (clone $summaryQuery)->where('status', 'open')->count(),
                'breakdown_hours' => (float) (clone $summaryQuery)->sum('breakdown_hours'),
                'total_cost' => (float) (clone $summaryQuery)->sum('total_cost'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        VehicleMaintenanceRecord::query()->create($this->withCalculatedCost($this->validatedData($request)));

        return back()->with('success', 'Fleet maintenance record added successfully.');
    }

    public function update(Request $request, int $record): RedirectResponse
    {
        $record = VehicleMaintenanceRecord::query()->forCurrentCompany()->findOrFail($record);
        $record->update($this->withCalculatedCost($this->validatedData($request)));

        return back()->with('success', 'Fleet maintenance record updated successfully.');
    }

    public function destroy(int $record): RedirectResponse
    {
        $record = VehicleMaintenanceRecord::query()->forCurrentCompany()->findOrFail($record);
        $record->delete();

        return back()->with('success', 'Fleet maintenance record deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::exists($this->tenantTable('vehicles'), 'id')->where('company_id', app(Tenant::class)->id())],
            'record_type' => ['required', Rule::in(['service', 'breakdown', 'idle', 'job_card', 'repair', 'depreciation'])],
            'job_card_no' => ['nullable', 'string', 'max:100'],
            'record_date' => ['required', 'date'],
            'next_service_date' => ['nullable', 'date'],
            'meter_reading' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'idle_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'breakdown_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'service_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'repair_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'fuel_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'depreciation_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'working_hours' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['required', Rule::in(['open', 'scheduled', 'in_progress', 'completed', 'cancelled'])],
            'description' => ['nullable', 'string', 'max:3000'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function withCalculatedCost(array $data): array
    {
        foreach (['meter_reading', 'idle_hours', 'breakdown_hours', 'service_cost', 'repair_cost', 'fuel_cost', 'depreciation_cost', 'working_hours'] as $field) {
            $data[$field] = (float) ($data[$field] ?? 0);
        }

        $data['total_cost'] = $data['service_cost'] + $data['repair_cost'] + $data['fuel_cost'] + $data['depreciation_cost'];
        $data['cost_per_hour'] = $data['working_hours'] > 0 ? round($data['total_cost'] / $data['working_hours'], 2) : 0;

        return $data;
    }

    private function tenantTable(string $table): string
    {
        $connectionName = app(Tenant::class)->connectionName();

        return $connectionName ? $connectionName.'.'.$table : $table;
    }
}
