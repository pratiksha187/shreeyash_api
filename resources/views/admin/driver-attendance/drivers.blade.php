@extends('admin.layouts.app')

@section('title', 'Vehicle Drivers | Admin Panel')
@section('headerTitle', 'Vehicle Drivers')
@section('headerSubtitle', 'Assign multiple drivers to each vehicle')

@section('content')
    <div class="page-header">
        <div>
            <h1>Vehicle Drivers</h1>
            <p>Create the driver master for vehicle-wise attendance.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.driver-attendance.index') }}">Driver Attendance</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.vehicle-drivers.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Driver</h2>
            <div class="form-grid three">
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
                    <label for="name">Driver Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="mobile">Mobile</label>
                    <input id="mobile" name="mobile" type="text" value="{{ old('mobile') }}">
                </div>
                <div class="field">
                    <label for="license_number">License Number</label>
                    <input id="license_number" name="license_number" type="text" value="{{ old('license_number') }}">
                </div>
                <div class="field full">
                    <label for="remarks">Remarks</label>
                    <input id="remarks" name="remarks" type="text" value="{{ old('remarks') }}">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Add Driver</button>
            </div>
        </section>
    </form>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Mobile</th>
                    <th>License</th>
                    <th>Status</th>
                    <th>Attendance</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($drivers as $driver)
                    <tr>
                        <td>{{ $driver->id }}</td>
                        <td>
                            <strong>{{ $driver->vehicle?->vehicle_number ?? '-' }}</strong>
                            @if ($driver->vehicle?->vehicle_type)
                                <div class="table-subtext">{{ $driver->vehicle->vehicle_type }}</div>
                            @endif
                        </td>
                        <td>{{ $driver->name }}</td>
                        <td>{{ $driver->mobile ?? '-' }}</td>
                        <td>{{ $driver->license_number ?? '-' }}</td>
                        <td>
                            <span class="status-pill {{ $driver->is_active ? 'status-approved' : 'status-rejected' }}">
                                {{ $driver->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $driver->attendances_count }}</td>
                        <td>{{ $driver->remarks ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="8">No vehicle drivers added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $drivers->links('admin.pagination') }}
    </div>
@endsection
