@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Leave Requests</h1>
            <p class="text-sm text-gray-500">View all approved or recorded leave entries.</p>
        </div>
    </div>

    <form method="GET" class="mb-6 grid gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium mb-1">Employee</label>
            <select name="employee_id" class="w-full rounded border-gray-300">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ $selectedEmployeeId == $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Month</label>
            <input type="month" name="month" value="{{ $selectedMonth }}" class="w-full rounded border-gray-300">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full rounded border-gray-300">
                <option value="">All</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ $selectedStatus == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded bg-blue-600 px-4 py-2 text-white">Filter</button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-lg border bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Employee</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Date</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($leaves as $leave)
                    <tr>
                        <td class="px-4 py-3 text-sm">{{ $leave->user?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $leave->attendance_date->toDateString() }}</td>
                        <td class="px-4 py-3 text-sm uppercase">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium">{{ $leave->leave_approval_status ?? 'pending' }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-col gap-2">
                                <div>{{ $leave->remarks ?? '-' }}</div>
                                <form method="POST" action="{{ route('admin.leave-requests.update', $leave) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="admin_note" placeholder="Note" value="{{ old('admin_note', $leave->leave_admin_note) }}" class="rounded border-gray-300 text-sm">
                                    <button type="submit" name="status" value="approved" class="rounded bg-green-600 px-3 py-1 text-xs font-semibold text-white">Approve</button>
                                    <button type="submit" name="status" value="rejected" class="rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No leave requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $leaves->links() }}
    </div>
</div>
@endsection
