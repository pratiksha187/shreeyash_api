@extends('admin.layouts.app')

@section('title', 'Vehicles | Admin Panel')
@section('headerTitle', 'Vehicles')
@section('headerSubtitle', 'Vehicle master list and daily calendar records')

@section('content')
    <div class="page-header">
        <div>
            <h1>Vehicles</h1>
            <p>Select a vehicle to add and review day-wise in and out records.</p>
        </div>
        <a class="btn" href="{{ route('admin.vehicles.create') }}">Add Vehicle</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle Number</th>
                    <th>Type</th>
                    <th>Owner</th>
                    <th>Driver</th>
                    <th>Mobile</th>
                    <th>Total Entries</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td>{{ $vehicle->id }}</td>
                        <td>
                            <a class="table-link" href="{{ route('admin.vehicles.show', $vehicle) }}">
                                {{ $vehicle->vehicle_number }}
                            </a>
                        </td>
                        <td>{{ $vehicle->vehicle_type ?? '-' }}</td>
                        <td>{{ $vehicle->owner_name ?? '-' }}</td>
                        <td>{{ $vehicle->driver_name ?? '-' }}</td>
                        <td>{{ $vehicle->driver_mobile ?? '-' }}</td>
                        <td>{{ $vehicle->vehicle_logs_count }}</td>
                        <td>{{ $vehicle->created_at?->format('d M Y') }}</td>
                        <td>
                            <a class="table-link" href="{{ route('admin.vehicles.show', $vehicle) }}">Calendar</a>
                            |
                            <a class="table-link" href="{{ route('admin.vehicles.edit', $vehicle) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="9">No vehicles added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $vehicles->links() }}
    </div>
@endsection
