@extends('admin.layouts.app')

@section('title', 'Attendance Reports | Admin Panel')
@section('headerTitle', 'Attendance Reports')
@section('headerSubtitle', 'Employee attendance and leave report')

@section('content')
    <div class="page-header">
        <div>
            <h1>Attendance Reports</h1>
            <p>Review employee attendance and leave entries between selected dates.</p>
        </div>
    </div>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.attendance-reports.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filters</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="from_date">From Date</label>
                    <input id="from_date" name="from_date" type="date" value="{{ old('from_date', $fromDate) }}">
                    @error('from_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="to_date">To Date</label>
                    <input id="to_date" name="to_date" type="date" value="{{ old('to_date', $toDate) }}">
                    @error('to_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
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
            <span>Total Records</span>
            <strong>{{ $summary['total_days'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Present</span>
            <strong>{{ $summary['present'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Leave</span>
            <strong>{{ $summary['leave'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Absent</span>
            <strong>{{ $summary['absent'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Half Day</span>
            <strong>{{ $summary['half_day'] }}</strong>
        </div>
    </section>

    <div class="page-header">
        <div>
            <h1>Employee Summary</h1>
            <p>Attendance counts for every employee in the selected date range.</p>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Mobile</th>
                    <th>Designation</th>
                    <th>Records</th>
                    <th>Present</th>
                    <th>Leave</th>
                    <th>Absent</th>
                    <th>Half Day</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employeeReports as $employee)
                    @php
                        $employeeAttendances = $employee->attendances;
                        $employeeLeaves = $employeeAttendances->where('status', 'leave');
                    @endphp
                    <tr>
                        <td>
                            <a class="table-link" href="{{ route('admin.employees.show', $employee) }}">
                                {{ $employee->name }}
                            </a>
                        </td>
                        <td>{{ $employee->mobile ?? '-' }}</td>
                        <td>{{ $employee->designation ?? '-' }}</td>
                        <td>{{ $employeeAttendances->count() }}</td>
                        <td>{{ $employeeAttendances->where('status', 'present')->count() }}</td>
                        <td>{{ $employeeLeaves->count() }}</td>
                        <td>{{ $employeeAttendances->where('status', 'absent')->count() }}</td>
                        <td>{{ $employeeAttendances->where('status', 'half_day')->count() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="8">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-header">
        <div>
            <h1>Leave Report</h1>
            <p>All leave entries found in the selected date range.</p>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Mobile</th>
                    <th>Designation</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leaveAttendances as $attendance)
                    <tr>
                        <td>{{ $attendance->attendance_date?->format('d M Y') }}</td>
                        <td>{{ $attendance->user?->name ?? '-' }}</td>
                        <td>{{ $attendance->user?->mobile ?? '-' }}</td>
                        <td>{{ $attendance->user?->designation ?? '-' }}</td>
                        <td>{{ $attendance->remarks ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="5">No leave records found for this date range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
