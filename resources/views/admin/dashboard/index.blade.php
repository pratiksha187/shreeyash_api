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
            <span>Pending Leave Requests</span>
            <strong>{{ $pendingLeaveRequests }}</strong>
        </div>
        <div class="card stat-card">
            <span>Active Projects</span>
            <strong>{{ $activeProjects }}</strong>
        </div>
        <div class="card stat-card">
            <span>Project Tasks</span>
            <strong>{{ $pendingProjectTasks }}</strong>
        </div>
        <div class="card stat-card">
            <span>Overdue Tasks</span>
            <strong>{{ $overdueProjectTasks }}</strong>
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
            <h1>Pending Leave Requests</h1>
            <p>Latest employee leave requests waiting for approval.</p>
        </div>
        <a class="btn" href="{{ route('admin.leave-requests.index', ['status' => 'pending']) }}">Review All</a>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentPendingLeaves as $leave)
                    @php($leaveType = $leave->leave_type ?? 'casual')
                    <tr>
                        <td>
                            @if ($leave->user)
                                <a class="table-link" href="{{ route('admin.employees.show', $leave->user) }}">
                                    {{ $leave->user->name }}
                                </a>
                                <div class="table-subtext">{{ $leave->user->designation ?? 'Employee' }}{{ $leave->user->mobile ? ' | '.$leave->user->mobile : '' }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <strong>{{ $leave->attendance_date?->format('d M Y') }}</strong>
                            <div class="table-subtext">{{ $leave->attendance_date?->format('l') }}</div>
                        </td>
                        <td>{{ \App\Models\Attendance::LEAVE_TYPES[$leaveType] ?? ucfirst($leaveType) }}</td>
                        <td class="text-wrap">{{ $leave->remarks ?: '-' }}</td>
                        <td>
                            <a class="btn small" href="{{ route('admin.leave-requests.index', ['status' => 'pending', 'employee_id' => $leave->user_id]) }}">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="5">No pending leave requests.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-header">
        <div>
            <h1>Today Present Employees</h1>
            <p>Employees marked present today with in and out time.</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('admin.leave-requests.index') }}">Leave Requests</a>
            <a class="btn secondary" href="{{ route('admin.attendance-reports.index') }}">Attendance Reports</a>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Mobile</th>
                    <th>Designation</th>
                    <th>In Time</th>
                    <th>Out Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($todayPresentEmployees as $attendance)
                    <tr>
                        <td>
                            @if ($attendance->user)
                                <a class="table-link" href="{{ route('admin.employees.show', $attendance->user) }}">
                                    {{ $attendance->user->name }}
                                </a>
                                <div class="table-subtext">{{ $attendance->user->email }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $attendance->user?->mobile ?? '-' }}</td>
                        <td>{{ $attendance->user?->designation ?? '-' }}</td>
                        <td>{{ $attendance->localCheckInAt()?->format('h:i A') ?? '-' }}</td>
                        <td>{{ $attendance->localCheckOutAt()?->format('h:i A') ?? 'Not checked out' }}</td>
                        <td>
                            <span class="status-pill status-present">Present</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="6">No employees are marked present today.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $todayPresentEmployees->links('admin.pagination') }}
    </div>

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

    <div class="page-header section-spacer">
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

    <div class="page-header section-spacer">
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
                    <th>PDF</th>
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
                        <td>
                            <a class="table-link" href="{{ route('admin.challans.download', $challan) }}">
                                {{ $challan->pdf_file_path ? 'Download PDF' : 'Generate PDF' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="8">No challans added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
