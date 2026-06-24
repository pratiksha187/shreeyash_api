@extends('admin.layouts.app')

@section('title', 'Employees | Admin Panel')
@section('headerTitle', 'Employees')
@section('headerSubtitle', 'Create and manage attendance employees')

@section('content')
    <div class="page-header">
        <div>
            <h1>Employees</h1>
            <p>All employees listed here can login to the attendance app using mobile and password.</p>
        </div>
        <a class="btn" href="{{ route('admin.employees.create') }}">Add Employee</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="card table-wrap employees-table-wrap">
        <table class="employees-table">
            <colgroup>
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 16%;">
                <col style="width: 16%;">
                <col style="width: 18%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Contact</th>
                    <th>Designation</th>
                    <th>Dates</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr>
                        <td data-label="Employee">
                            <div class="employee-name">
                                <a class="table-link" href="{{ route('admin.employees.show', $employee) }}">
                                    {{ $employee->name }}
                                </a>
                                <span class="employee-id">ID #{{ $employee->id }}</span>
                            </div>
                        </td>
                        <td data-label="Contact">
                            <div class="employee-contact">
                                <span>{{ $employee->email }}</span>
                                <span class="employee-subtext">{{ $employee->mobile ?? 'No mobile' }}</span>
                            </div>
                        </td>
                        <td data-label="Designation">
                            <span class="designation-pill">{{ $employee->designation ?? 'Employee' }}</span>
                        </td>
                        <td data-label="Dates">
                            <div class="date-stack">
                                <span>{{ $employee->join_date?->format('d M Y') ?? '-' }}</span>
                                <span class="employee-subtext">Created {{ $employee->created_at?->format('d M Y') }}</span>
                            </div>
                        </td>
                        <td data-label="Action">
                            <div class="employee-actions">
                                <a class="btn small secondary" href="{{ route('admin.employees.edit', $employee) }}">Edit</a>
                                <form class="employee-action-form" method="POST" action="{{ route('admin.employees.send-credentials', $employee) }}" target="_blank" onsubmit="return confirm('Generate a new password and open WhatsApp Web with the credentials message?');">
                                    @csrf
                                    <button class="btn small whatsapp" type="submit">Open WhatsApp</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="5">No employees added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $employees->links('admin.pagination') }}
    </div>
@endsection
