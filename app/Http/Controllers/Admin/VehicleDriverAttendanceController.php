<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleDriver;
use App\Models\VehicleDriverAttendance;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleDriverAttendanceController extends Controller
{
    public function drivers(): View
    {
        return view('admin.driver-attendance.drivers', [
            'vehicles' => Vehicle::query()
                ->forCurrentCompany()
                ->orderBy('vehicle_number')
                ->get(['id', 'vehicle_number', 'vehicle_type']),
            'drivers' => VehicleDriver::query()
                ->forCurrentCompany()
                ->with('vehicle:id,vehicle_number,vehicle_type')
                ->withCount('attendances')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    public function storeDriver(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', Rule::exists($this->tenantTable('vehicles'), 'id')->where('company_id', app(Tenant::class)->id())],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        VehicleDriver::query()->create($data);

        return redirect()->route('admin.vehicle-drivers.index')->with('success', 'Vehicle driver added successfully.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'vehicle_id' => ['nullable', Rule::exists($this->tenantTable('vehicles'), 'id')->where('company_id', app(Tenant::class)->id())],
            'vehicle_driver_id' => ['nullable', Rule::exists($this->tenantTable('vehicle_drivers'), 'id')->where('company_id', app(Tenant::class)->id())],
            'status' => ['nullable', Rule::in(VehicleDriverAttendance::STATUSES)],
        ]);

        $fromDate = isset($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : today()->toDateString();

        $baseQuery = VehicleDriverAttendance::query()
            ->forCurrentCompany()
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->when(isset($filters['vehicle_id']), fn ($query) => $query->where('vehicle_id', $filters['vehicle_id']))
            ->when(isset($filters['vehicle_driver_id']), fn ($query) => $query->where('vehicle_driver_id', $filters['vehicle_driver_id']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']));

        return view('admin.driver-attendance.index', [
            'attendances' => (clone $baseQuery)
                ->with(['vehicle:id,vehicle_number,vehicle_type', 'driver:id,name,mobile,vehicle_id'])
                ->orderByDesc('attendance_date')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'vehicles' => Vehicle::query()
                ->forCurrentCompany()
                ->orderBy('vehicle_number')
                ->get(['id', 'vehicle_number', 'vehicle_type']),
            'drivers' => VehicleDriver::query()
                ->forCurrentCompany()
                ->with('vehicle:id,vehicle_number')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'vehicle_id', 'name', 'mobile']),
            'statuses' => VehicleDriverAttendance::STATUSES,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedVehicleId' => $filters['vehicle_id'] ?? null,
            'selectedDriverId' => $filters['vehicle_driver_id'] ?? null,
            'selectedStatus' => $filters['status'] ?? null,
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'present' => (clone $baseQuery)->where('status', 'present')->count(),
                'absent' => (clone $baseQuery)->where('status', 'absent')->count(),
                'half_day' => (clone $baseQuery)->where('status', 'half_day')->count(),
                'leave' => (clone $baseQuery)->where('status', 'leave')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', Rule::exists($this->tenantTable('vehicles'), 'id')->where('company_id', app(Tenant::class)->id())],
            'vehicle_driver_id' => ['required', Rule::exists($this->tenantTable('vehicle_drivers'), 'id')->where('company_id', app(Tenant::class)->id())],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(VehicleDriverAttendance::STATUSES)],
            'in_time' => ['nullable', 'date_format:H:i'],
            'out_time' => ['nullable', 'date_format:H:i'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $driver = VehicleDriver::query()
            ->forCurrentCompany()
            ->whereKey($data['vehicle_driver_id'])
            ->where('vehicle_id', $data['vehicle_id'])
            ->first();

        if (! $driver) {
            return back()
                ->withErrors(['vehicle_driver_id' => 'Selected driver is not assigned to this vehicle.'])
                ->withInput();
        }

        VehicleDriverAttendance::query()->updateOrCreate(
            [
                'vehicle_driver_id' => $data['vehicle_driver_id'],
                'attendance_date' => Carbon::parse($data['attendance_date'])->toDateString(),
            ],
            [
                'vehicle_id' => $data['vehicle_id'],
                'status' => $data['status'],
                'in_time' => $data['in_time'] ?? null,
                'out_time' => $data['out_time'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]
        );

        return redirect()->route('admin.driver-attendance.index')->with('success', 'Driver attendance saved successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'vehicle_id' => ['nullable', Rule::exists($this->tenantTable('vehicles'), 'id')->where('company_id', app(Tenant::class)->id())],
            'vehicle_driver_id' => ['nullable', Rule::exists($this->tenantTable('vehicle_drivers'), 'id')->where('company_id', app(Tenant::class)->id())],
            'status' => ['nullable', Rule::in(VehicleDriverAttendance::STATUSES)],
        ]);

        $fromDate = isset($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : today()->toDateString();

        $attendances = VehicleDriverAttendance::query()
            ->forCurrentCompany()
            ->with(['vehicle:id,vehicle_number,vehicle_type', 'driver:id,name,mobile,vehicle_id'])
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->when(isset($filters['vehicle_id']), fn ($query) => $query->where('vehicle_id', $filters['vehicle_id']))
            ->when(isset($filters['vehicle_driver_id']), fn ($query) => $query->where('vehicle_driver_id', $filters['vehicle_driver_id']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();

        $filename = 'driver-attendance-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($attendances) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sr. No.', 'Date', 'Vehicle', 'Vehicle Type', 'Driver', 'Mobile', 'Status', 'In Time', 'Out Time', 'Remarks']);

            foreach ($attendances as $index => $attendance) {
                fputcsv($handle, [
                    $index + 1,
                    $attendance->attendance_date?->format('d-m-Y') ?? '',
                    $attendance->vehicle?->vehicle_number ?? '',
                    $attendance->vehicle?->vehicle_type ?? '',
                    $attendance->driver?->name ?? '',
                    $attendance->driver?->mobile ?? '',
                    str_replace('_', ' ', ucfirst($attendance->status)),
                    $attendance->in_time ?? '',
                    $attendance->out_time ?? '',
                    $attendance->remarks ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function edit(int $vehicleDriverAttendance): View
    {
        return view('admin.driver-attendance.edit', [
            'attendance' => $this->findAttendance($vehicleDriverAttendance),
            'vehicles' => Vehicle::query()
                ->forCurrentCompany()
                ->orderBy('vehicle_number')
                ->get(['id', 'vehicle_number', 'vehicle_type']),
            'drivers' => VehicleDriver::query()
                ->forCurrentCompany()
                ->with('vehicle:id,vehicle_number')
                ->orderBy('name')
                ->get(['id', 'vehicle_id', 'name', 'mobile']),
            'statuses' => VehicleDriverAttendance::STATUSES,
        ]);
    }

    public function update(Request $request, int $vehicleDriverAttendance): RedirectResponse
    {
        $attendance = $this->findAttendance($vehicleDriverAttendance);

        $data = $request->validate([
            'vehicle_id' => ['required', Rule::exists($this->tenantTable('vehicles'), 'id')->where('company_id', app(Tenant::class)->id())],
            'vehicle_driver_id' => ['required', Rule::exists($this->tenantTable('vehicle_drivers'), 'id')->where('company_id', app(Tenant::class)->id())],
            'attendance_date' => [
                'required',
                'date',
                Rule::unique($this->tenantTable('vehicle_driver_attendances'), 'attendance_date')
                    ->where('vehicle_driver_id', $request->input('vehicle_driver_id'))
                    ->ignore($attendance->id),
            ],
            'status' => ['required', Rule::in(VehicleDriverAttendance::STATUSES)],
            'in_time' => ['nullable', 'date_format:H:i'],
            'out_time' => ['nullable', 'date_format:H:i'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $driver = VehicleDriver::query()
            ->forCurrentCompany()
            ->whereKey($data['vehicle_driver_id'])
            ->where('vehicle_id', $data['vehicle_id'])
            ->first();

        if (! $driver) {
            return back()
                ->withErrors(['vehicle_driver_id' => 'Selected driver is not assigned to this vehicle.'])
                ->withInput();
        }

        $attendance->update([
            'vehicle_id' => $data['vehicle_id'],
            'vehicle_driver_id' => $data['vehicle_driver_id'],
            'attendance_date' => Carbon::parse($data['attendance_date'])->toDateString(),
            'status' => $data['status'],
            'in_time' => $data['in_time'] ?? null,
            'out_time' => $data['out_time'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);

        return redirect()->route('admin.driver-attendance.index')->with('success', 'Driver attendance updated successfully.');
    }

    private function findAttendance(int $vehicleDriverAttendance): VehicleDriverAttendance
    {
        return VehicleDriverAttendance::query()
            ->forCurrentCompany()
            ->with(['vehicle:id,vehicle_number,vehicle_type', 'driver:id,name,mobile,vehicle_id'])
            ->findOrFail($vehicleDriverAttendance);
    }

    private function tenantTable(string $table): string
    {
        $connection = app(Tenant::class)->connectionName();

        return $connection ? $connection.'.'.$table : $table;
    }
}
