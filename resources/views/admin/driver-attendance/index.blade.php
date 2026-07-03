@extends('admin.layouts.app')

@section('title', 'Driver Attendance | Admin Panel')
@section('headerTitle', 'Driver Attendance')
@section('headerSubtitle', 'Vehicle-wise driver attendance records')

@section('content')
    <div class="page-header">
        <div>
            <h1>Driver Attendance</h1>
            <p>Mark attendance for the driver assigned to a particular vehicle.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.vehicle-drivers.index') }}">Vehicle Drivers</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.driver-attendance.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Attendance</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="attendance_date">Date</label>
                    <input id="attendance_date" name="attendance_date" type="date" value="{{ old('attendance_date', today()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label for="vehicle_id">Vehicle</label>
                    <select id="vehicle_id" name="vehicle_id" required>
                        <option value="">Select vehicle</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                {{ $vehicle->vehicle_number }}{{ $vehicle->vehicle_type ? ' - '.$vehicle->vehicle_type : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="vehicle_driver_id">Driver</label>
                    <select id="vehicle_driver_id" name="vehicle_driver_id" required>
                        <option value="">Select driver</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}" data-vehicle-id="{{ $driver->vehicle_id }}" @selected(old('vehicle_driver_id') == $driver->id)>
                                {{ $driver->name }} - {{ $driver->vehicle?->vehicle_number ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', 'present') === $status)>
                                {{ str_replace('_', ' ', ucfirst($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="in_time">In Time</label>
                    <input id="in_time" name="in_time" type="time" value="{{ old('in_time') }}">
                </div>
                <div class="field">
                    <label for="out_time">Out Time</label>
                    <input id="out_time" name="out_time" type="time" value="{{ old('out_time') }}">
                </div>
                <div class="field full">
                    <label for="remarks">Remarks</label>
                    <input id="remarks" name="remarks" type="text" value="{{ old('remarks') }}">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Save Attendance</button>
            </div>
        </section>
    </form>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.driver-attendance.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filters</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="from_date">From Date</label>
                    <input id="from_date" name="from_date" type="date" value="{{ old('from_date', $fromDate) }}">
                </div>
                <div class="field">
                    <label for="to_date">To Date</label>
                    <input id="to_date" name="to_date" type="date" value="{{ old('to_date', $toDate) }}">
                </div>
                <div class="field">
                    <label for="filter_vehicle">Vehicle</label>
                    <select id="filter_vehicle" name="vehicle_id">
                        <option value="">All vehicles</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected((string) $selectedVehicleId === (string) $vehicle->id)>
                                {{ $vehicle->vehicle_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filter_driver">Driver</label>
                    <select id="filter_driver" name="vehicle_driver_id">
                        <option value="">All drivers</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}" @selected((string) $selectedDriverId === (string) $driver->id)>
                                {{ $driver->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filter_status">Status</label>
                    <select id="filter_status" name="status">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>
                                {{ str_replace('_', ' ', ucfirst($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Apply Filter</button>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total</span>
            <strong>{{ $summary['total'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Present</span>
            <strong>{{ $summary['present'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Absent</span>
            <strong>{{ $summary['absent'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Half Day</span>
            <strong>{{ $summary['half_day'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Leave</span>
            <strong>{{ $summary['leave'] }}</strong>
        </div>
    </section>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>In</th>
                    <th>Out</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->attendance_date?->format('d M Y') }}</td>
                        <td>
                            <strong>{{ $attendance->vehicle?->vehicle_number ?? '-' }}</strong>
                            @if ($attendance->vehicle?->vehicle_type)
                                <div class="table-subtext">{{ $attendance->vehicle->vehicle_type }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $attendance->driver?->name ?? '-' }}
                            @if ($attendance->driver?->mobile)
                                <div class="table-subtext">{{ $attendance->driver->mobile }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $attendance->status === 'present' ? 'approved' : ($attendance->status === 'absent' ? 'rejected' : 'pending') }}">
                                {{ str_replace('_', ' ', ucfirst($attendance->status)) }}
                            </span>
                        </td>
                        <td>{{ $attendance->in_time ?? '-' }}</td>
                        <td>{{ $attendance->out_time ?? '-' }}</td>
                        <td>{{ $attendance->remarks ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No driver attendance found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $attendances->links('admin.pagination') }}
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const vehicleSelect = document.getElementById('vehicle_id');
            const driverSelect = document.getElementById('vehicle_driver_id');
            const options = Array.from(driverSelect.options);

            function syncDrivers() {
                const vehicleId = vehicleSelect.value;

                options.forEach(function (option) {
                    if (! option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = vehicleId && option.dataset.vehicleId !== vehicleId;
                });

                const selected = driverSelect.selectedOptions[0];

                if (selected && selected.hidden) {
                    driverSelect.value = '';
                }
            }

            vehicleSelect.addEventListener('change', syncDrivers);
            syncDrivers();
        });
    </script>
@endsection
