@extends('admin.layouts.app')

@section('title', 'Missed Requests | Admin Panel')
@section('headerTitle', 'Missed Requests')
@section('headerSubtitle', 'Employee missed attendance correction requests')

@section('content')
    <div class="page-header">
        <div>
            <h1>Missed Requests</h1>
            <p>Review requests for missed clock in, clock out, or full day attendance.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.missed-requests.index') }}">
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
                    <label for="user_id">Employee</label>
                    <select id="user_id" name="user_id">
                        <option value="">All Employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) $selectedUserId === (string) $employee->id)>
                                {{ $employee->name }}{{ $employee->mobile ? ' - ' . $employee->mobile : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="request_for">Request For</label>
                    <select id="request_for" name="request_for">
                        <option value="">All Types</option>
                        @foreach ($requestTypes as $requestType)
                            <option value="{{ $requestType }}" @selected($selectedRequestFor === $requestType)>
                                {{ str_replace('_', ' ', ucfirst($requestType)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('request_for')
                        <div class="error">{{ $message }}</div>
                    @enderror
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
                    @error('status')
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
            <span>Total Requests</span>
            <strong>{{ $summary['total'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Pending</span>
            <strong>{{ $summary['pending'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Approved</span>
            <strong>{{ $summary['approved'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Rejected</span>
            <strong>{{ $summary['rejected'] }}</strong>
        </div>
    </section>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Attendance Date</th>
                    <th>Employee</th>
                    <th>Request For</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Admin Update</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $missedRequest)
                    @php
                        $attendance = $attendanceTimes->get(
                            $missedRequest->user_id.'|'.$missedRequest->attendance_date->toDateString()
                        );
                        $showRowErrors = (string) old('request_id') === (string) $missedRequest->id;
                    @endphp
                    <tr>
                        <td>{{ $missedRequest->attendance_date?->format('d M Y') }}</td>
                        <td>
                            @if ($missedRequest->user)
                                <a class="table-link" href="{{ route('admin.employees.show', $missedRequest->user) }}">
                                    {{ $missedRequest->user->name }}
                                </a>
                                <div class="table-subtext">
                                    {{ $missedRequest->user->designation ?? 'Employee' }}
                                    @if ($missedRequest->user->mobile)
                                        <br>{{ $missedRequest->user->mobile }}
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ str_replace('_', ' ', ucfirst($missedRequest->request_for)) }}</td>
                        <td class="text-wrap">
                            {{ $missedRequest->reason }}
                            @if ($missedRequest->admin_note)
                                <div class="table-subtext">Admin note: {{ $missedRequest->admin_note }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $missedRequest->status }}">
                                {{ $missedRequest->status }}
                            </span>
                            @if ($missedRequest->reviewed_at)
                                <div class="table-subtext">
                                    {{ $missedRequest->reviewed_at->format('d M Y h:i A') }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $missedRequest->created_at?->format('d M Y h:i A') }}</td>
                        <td>
                            <form class="inline-status-form" method="POST" action="{{ route('admin.missed-requests.update', $missedRequest) }}">
                                @csrf
                                @method('PATCH')
                                <input name="request_id" type="hidden" value="{{ $missedRequest->id }}">
                                <select name="status" required>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(($showRowErrors ? old('status') : $missedRequest->status) === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="inline-time-fields">
                                    <label>
                                        In Time
                                        <input name="check_in_time" type="time" value="{{ $showRowErrors ? old('check_in_time') : $attendance?->localCheckInAt()?->format('H:i') }}">
                                    </label>
                                    <label>
                                        Out Time
                                        <input name="check_out_time" type="time" value="{{ $showRowErrors ? old('check_out_time') : $attendance?->localCheckOutAt()?->format('H:i') }}">
                                    </label>
                                </div>
                                @if ($showRowErrors)
                                    @error('check_in_time')
                                        <div class="error">{{ $message }}</div>
                                    @enderror
                                    @error('check_out_time')
                                        <div class="error">{{ $message }}</div>
                                    @enderror
                                @endif
                                <textarea name="admin_note" placeholder="Admin note">{{ $showRowErrors ? old('admin_note') : $missedRequest->admin_note }}</textarea>
                                <button class="btn" type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No missed attendance requests found for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $requests->links('admin.pagination') }}
    </div>
@endsection
