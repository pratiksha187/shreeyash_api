@extends('admin.layouts.app')

@section('title', 'Edit Driver Attendance | Admin Panel')
@section('headerTitle', 'Edit Driver Attendance')
@section('headerSubtitle', 'Update vehicle-wise driver attendance')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Driver Attendance</h1>
            <p>{{ $attendance->driver?->name ?? 'Driver' }} - {{ $attendance->vehicle?->vehicle_number ?? 'Vehicle' }}</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.driver-attendance.index') }}">Back to Attendance</a>
    </div>

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.driver-attendance.update', $attendance) }}">
        @csrf
        @method('PUT')
        <section class="form-section">
            <h2 class="section-title">Attendance Details</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="attendance_date">Date</label>
                    <input id="attendance_date" name="attendance_date" type="date" value="{{ old('attendance_date', $attendance->attendance_date?->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label for="vehicle_id">Vehicle</label>
                    <select id="vehicle_id" name="vehicle_id" required>
                        <option value="">Select vehicle</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id', $attendance->vehicle_id) === (string) $vehicle->id)>
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
                            <option value="{{ $driver->id }}" data-vehicle-id="{{ $driver->vehicle_id }}" @selected((string) old('vehicle_driver_id', $attendance->vehicle_driver_id) === (string) $driver->id)>
                                {{ $driver->name }} - {{ $driver->vehicle?->vehicle_number ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $attendance->status) === $status)>
                                {{ str_replace('_', ' ', ucfirst($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="in_time">In Time</label>
                    <input id="in_time" name="in_time" type="time" value="{{ old('in_time', $attendance->in_time ? substr($attendance->in_time, 0, 5) : null) }}">
                </div>
                <div class="field">
                    <label for="out_time">Out Time</label>
                    <input id="out_time" name="out_time" type="time" value="{{ old('out_time', $attendance->out_time ? substr($attendance->out_time, 0, 5) : null) }}">
                </div>
                <div class="field full">
                    <label for="remarks">Remarks</label>
                    <input id="remarks" name="remarks" type="text" value="{{ old('remarks', $attendance->remarks) }}">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Update Attendance</button>
                <a class="btn secondary" href="{{ route('admin.driver-attendance.index') }}">Cancel</a>
            </div>
        </section>
    </form>

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
