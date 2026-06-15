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

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Designation</th>
                    <th>Join Date</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr>
                        <td>{{ $employee->id }}</td>
                        <td>
                            <a class="table-link" href="{{ route('admin.employees.show', $employee) }}">
                                {{ $employee->name }}
                            </a>
                        </td>
                        <td>{{ $employee->email }}</td>
                        <td>{{ $employee->mobile ?? '-' }}</td>
                        <td>{{ $employee->designation ?? '-' }}</td>
                        <td>{{ $employee->join_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $employee->created_at?->format('d M Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.employees.send-credentials', $employee) }}" onsubmit="return confirm('Generate a new password and send login credentials on WhatsApp?');">
                                @csrf
                                <button class="btn small" type="submit">Send Credentials</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="8">No employees added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $employees->links() }}
    </div>
@endsection
