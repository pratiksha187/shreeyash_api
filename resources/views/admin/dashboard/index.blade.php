@extends('admin.layouts.app')

@section('title', 'Dashboard | Admin Panel')
@section('headerTitle', 'Dashboard')
@section('headerSubtitle', 'Attendance overview and employee activity')

@section('content')
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p>Quick view of employees and today attendance status.</p>
        </div>
        <a class="btn" href="{{ route('admin.employees.create') }}">Add Employee</a>
    </div>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total Employees</span>
            <strong>{{ $totalEmployees }}</strong>
        </div>
        <div class="card stat-card">
            <span>Today Present</span>
            <strong>{{ $todayPresent }}</strong>
        </div>
        <div class="card stat-card">
            <span>Today Leave</span>
            <strong>{{ $todayLeave }}</strong>
        </div>
        <div class="card stat-card">
            <span>Today Absent</span>
            <strong>{{ $todayAbsent }}</strong>
        </div>
        <div class="card stat-card">
            <span>Total Vehicles</span>
            <strong>{{ $totalVehicles }}</strong>
        </div>
        <div class="card stat-card">
            <span>Total Challans</span>
            <strong>{{ $totalChallans }}</strong>
        </div>
        <div class="card stat-card">
            <span>Vehicle Entries Today</span>
            <strong>{{ $todayVehicles }}</strong>
        </div>
        <div class="card stat-card">
            <span>Vehicles Inside</span>
            <strong>{{ $vehiclesInside }}</strong>
        </div>
    </section>

    <div class="page-header">
        <div>
            <h1>Recent Employees</h1>
            <p>Latest employees added to the system.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.employees.index') }}">View All</a>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentEmployees as $employee)
                    <tr>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->email }}</td>
                        <td>{{ $employee->mobile ?? '-' }}</td>
                        <td>{{ $employee->created_at?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="4">No employees added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-header" style="margin-top: 28px;">
        <div>
            <h1>Recent Vehicles</h1>
            <p>Latest vehicle in and out records.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.vehicles.index') }}">View All</a>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Vehicle Number</th>
                    <th>Driver</th>
                    <th>In Time</th>
                    <th>Out Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentVehicleLogs as $vehicleLog)
                    <tr>
                        <td>{{ $vehicleLog->vehicle?->vehicle_number ?? $vehicleLog->vehicle_number }}</td>
                        <td>{{ $vehicleLog->driver_name ?? '-' }}</td>
                        <td>{{ $vehicleLog->in_at?->format('d M Y h:i A') }}</td>
                        <td>{{ $vehicleLog->out_at?->format('d M Y h:i A') ?? 'Inside' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="4">No vehicle logs added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-header" style="margin-top: 28px;">
        <div>
            <h1>Recent Challans</h1>
            <p>Latest challan records added to the system.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.challans.index') }}">View All</a>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Challan No.</th>
                    <th>Date</th>
                    <th>Party Name</th>
                    <th>Material / M/c</th>
                    <th>Vehicle No.</th>
                    <th>Location</th>
                    <th>Submitted By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentChallans as $challan)
                    <tr>
                        <td>{{ $challan->challan_no }}</td>
                        <td>{{ $challan->challan_date?->format('d M Y') }}</td>
                        <td class="text-wrap">{{ $challan->party_name }}</td>
                        <td class="text-wrap">{{ $challan->material_machine }}</td>
                        <td>{{ $challan->vehicle_no ?? '-' }}</td>
                        <td>{{ $challan->location ?? '-' }}</td>
                        <td>
                            @if ($challan->user)
                                <a class="table-link" href="{{ route('admin.employees.show', $challan->user) }}">{{ $challan->user->name }}</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No challans added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
