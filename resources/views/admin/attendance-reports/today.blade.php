@extends('admin.layouts.app')

@section('title', 'Today Attendance | Admin Panel')
@section('bodyClass', 'today-attendance-page')
@section('headerTitle', 'Today Attendance')
@section('headerSubtitle', 'Live HR attendance overview')

@section('content')
    <div class="page-header">
        <div>
            <h1>Today Attendance</h1>
            <p>Review attendance marked for {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}.</p>
        </div>
    </div>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.today-attendance.index') }}">
        <section class="form-section">
            <h2 class="section-title">Select Date</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="date">Date</label>
                    <input id="date" name="date" type="date" value="{{ old('date', $selectedDate) }}">
                    @error('date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Show Attendance</button>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <a class="btn secondary" href="{{ route('admin.today-attendance.index') }}">Today</a>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid today-attendance-stats">
        <div class="card stat-card">
            <span>Total Employees</span>
            <strong>{{ $summary['total_employees'] }}</strong>
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
        <div class="card stat-card">
            <span>Not Marked</span>
            <strong>{{ $summary['not_marked'] }}</strong>
        </div>
    </section>

    <div class="card table-wrap today-attendance-table-wrap">
        <table class="today-attendance-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Mobile</th>
                    <th>Designation</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    @php
                        $attendance = $employee->todayAttendance;
                        $status = $attendance?->status ?? 'not_marked';
                    @endphp
                    <tr>
                        <td data-label="Employee">
                            <a class="table-link" href="{{ route('admin.employees.show', $employee) }}">
                                {{ $employee->name }}
                            </a>
                            <div class="table-subtext">{{ $employee->email ?? 'No email' }}</div>
                        </td>
                        <td data-label="Mobile">{{ $employee->mobile ?? '-' }}</td>
                        <td data-label="Designation">{{ $employee->designation ?? '-' }}</td>
                        <td data-label="Status">
                            <span class="status-pill status-{{ $status === 'not_marked' ? 'empty' : $status }}">
                                {{ str_replace('_', ' ', $status) }}
                            </span>
                            @if ($attendance?->leave_approval_status)
                                <div class="table-subtext">
                                    Leave: {{ ucfirst($attendance->leave_approval_status) }}
                                </div>
                            @endif
                        </td>
                        <td data-label="Check In">
                            {{ $attendance?->localCheckInAt()?->format('h:i A') ?? '-' }}
                        </td>
                        <td data-label="Check Out">
                            {{ $attendance?->localCheckOutAt()?->format('h:i A') ?? '-' }}
                        </td>
                        <td class="text-wrap" data-label="Remarks">{{ $attendance?->remarks ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $employees->links('admin.pagination') }}
    </div>
@endsection
