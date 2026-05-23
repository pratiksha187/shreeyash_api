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
@endsection
