@extends('admin.layouts.app')

@section('title', 'Leave Requests | Admin Panel')
@section('bodyClass', 'leave-requests-page')
@section('headerTitle', 'Leave Requests')
@section('headerSubtitle', 'Review employee leave approvals')

@section('content')
    <div class="page-header">
        <div>
            <h1>Leave Requests</h1>
            <p>Review recorded leave entries and approve or reject pending requests. Leave limits are checked from employee leave entitlement records, with 4 casual, 4 sick, and 4 paid leaves used as the default.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.leave-requests.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filters</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="employee_id">Employee</label>
                    <select id="employee_id" name="employee_id">
                        <option value="">All Employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) $selectedEmployeeId === (string) $employee->id)>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="month">Month</label>
                    <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="leave_type">Leave Type</label>
                    <select id="leave_type" name="leave_type">
                        <option value="">All Types</option>
                        @foreach ($leaveTypes as $type => $label)
                            <option value="{{ $type }}" @selected($selectedLeaveType === $type)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field leave-filter-action">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Apply Filter</button>
                </div>
            </div>
        </section>
    </form>

    <div class="card table-wrap leave-requests-table-wrap">
        <table class="leave-requests-table">
            <colgroup>
                <col class="leave-employee-column">
                <col class="leave-date-column">
                <col class="leave-type-column">
                <col class="leave-status-column">
                <col class="leave-year-column">
                <col class="leave-remarks-column">
                <col class="leave-action-column">
            </colgroup>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Leave Year</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leaves as $leave)
                    @php($approvalStatus = $leave->leave_approval_status ?? 'pending')
                    @php($leaveType = $leave->leave_type ?? 'casual')
                    <tr>
                        <td data-label="Employee">
                            @if ($leave->user)
                                <a class="table-link" href="{{ route('admin.employees.show', $leave->user) }}">
                                    {{ $leave->user->name }}
                                </a>
                                <div class="table-subtext">{{ $leave->user->designation ?? 'Employee' }}</div>
                            @else
                                N/A
                            @endif
                        </td>
                        <td data-label="Date">
                            <div class="date-stack">
                                <strong>{{ $leave->attendance_date?->format('d M Y') }}</strong>
                                <span class="table-subtext">{{ $leave->attendance_date?->format('l') }}</span>
                            </div>
                        </td>
                        <td data-label="Type">
                            {{ $leaveTypes[$leaveType] ?? ucfirst($leaveType) }}
                        </td>
                        <td data-label="Status">
                            <span class="status-pill status-{{ $approvalStatus }}">
                                {{ $approvalStatus }}
                            </span>
                        </td>
                        <td data-label="Leave Year">
                            @php($usage = $leaveUsage[$leave->id] ?? null)
                            @if ($usage)
                                <strong>{{ $usage['used'] }}/{{ $usage['total_limit'] }}</strong>
                                <div class="table-subtext">
                                    {{ $usage['remaining'] }} remaining
                                    <br>
                                    {{ $usage['start']->format('d M Y') }} - {{ $usage['end']->format('d M Y') }}
                                    <br>
                                    CL {{ $usage['by_type']['casual'] ?? 0 }}/{{ $usage['limits']['casual'] ?? 0 }},
                                    SL {{ $usage['by_type']['sick'] ?? 0 }}/{{ $usage['limits']['sick'] ?? 0 }},
                                    PL {{ $usage['by_type']['paid'] ?? 0 }}/{{ $usage['limits']['paid'] ?? 0 }}
                                    <br>
                                    {{ $usage['source'] === 'database' ? 'DB entitlement' : 'Default entitlement' }}
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="leave-remarks-cell" data-label="Remarks">
                            <div class="leave-reason">{{ $leave->remarks ?: 'No employee remark added.' }}</div>
                            @if ($leave->leave_admin_note)
                                <div class="table-subtext">Admin note: {{ $leave->leave_admin_note }}</div>
                            @endif
                        </td>
                        <td class="leave-action-cell" data-label="Action">
                            <form method="POST" action="{{ route('admin.leave-requests.update', $leave) }}" class="leave-action-form">
                                @csrf
                                @method('PATCH')
                                <label for="leave_type_{{ $leave->id }}">Leave Type</label>
                                <select id="leave_type_{{ $leave->id }}" name="leave_type" required>
                                    @foreach ($leaveTypes as $type => $label)
                                        <option value="{{ $type }}" @selected(old('leave_type', $leaveType) === $type)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="admin_note_{{ $leave->id }}">Admin Note</label>
                                <input
                                    id="admin_note_{{ $leave->id }}"
                                    type="text"
                                    name="admin_note"
                                    placeholder="Add note"
                                    value="{{ old('admin_note', $leave->leave_admin_note) }}"
                                >
                                <div class="leave-action-buttons">
                                    <button type="submit" name="status" value="approved" class="btn small leave-approve-button">Approve</button>
                                    <button type="submit" name="status" value="rejected" class="btn small danger">Reject</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty">No leave requests found for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $leaves->links('admin.pagination') }}
    </div>
@endsection
