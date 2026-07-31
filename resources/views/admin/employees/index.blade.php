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

    <form class="card form-card report-filter employee-search-form" method="GET" action="{{ route('admin.employees.index') }}">
        <section class="form-section">
            <h2 class="section-title">Search Employee</h2>
            <div class="form-grid three">
                <div class="field employee-search-field">
                    <label for="search">Employee Search</label>
                    <input
                        id="search"
                        name="search"
                        type="search"
                        value="{{ old('search', $search) }}"
                        placeholder="Search name, mobile, email, designation or ID"
                    >
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="" @selected($status === '')>All Employees</option>
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Search</button>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <a class="btn secondary" href="{{ route('admin.employees.index') }}">Clear</a>
                </div>
            </div>
        </section>
    </form>

    <div class="card table-wrap employees-table-wrap">
        <table class="employees-table">
            <colgroup>
                <col style="width: 22%;">
                <col style="width: 24%;">
                <col style="width: 13%;">
                <col style="width: 10%;">
                <col style="width: 12%;">
                <col style="width: 12%;">
                <col style="width: 14%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Contact</th>
                    <th>Designation</th>
                    <th>Status</th>
                    <th>Birthday</th>
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
                        <td data-label="Status">
                            <span class="status-pill {{ $employee->is_active ? 'status-approved' : 'status-rejected' }}">
                                {{ $employee->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td data-label="Birthday">
                            <div class="date-stack">
                                <span>{{ $employee->date_of_birth?->format('d M Y') ?? '-' }}</span>
                            </div>
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
                        <td class="empty" colspan="7">
                            {{ $search !== '' || $status !== '' ? 'No employees found for this search.' : 'No employees added yet.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $employees->links('admin.pagination') }}
    </div>
@endsection
