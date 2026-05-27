@extends('admin.layouts.app')

@section('title', 'Complaints | Admin Panel')
@section('headerTitle', 'Complaints')
@section('headerSubtitle', 'Employee complaint box submissions')

@section('content')
    <div class="page-header">
        <div>
            <h1>Complaints</h1>
            <p>Review complaint box submissions with employee details and response status.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.complaints.index') }}">
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
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>
                                {{ str_replace('_', ' ', ucfirst($status)) }}
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
            <span>Total Complaints</span>
            <strong>{{ $summary['total'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Open</span>
            <strong>{{ $summary['open'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>In Progress</span>
            <strong>{{ $summary['in_progress'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Resolved</span>
            <strong>{{ $summary['resolved'] }}</strong>
        </div>
    </section>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Subject</th>
                    <th>Complaint</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Admin Update</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($complaints as $complaint)
                    <tr>
                        <td>{{ $complaint->created_at?->format('d M Y h:i A') }}</td>
                        <td>
                            @if ($complaint->user)
                                <a class="table-link" href="{{ route('admin.employees.show', $complaint->user) }}">
                                    {{ $complaint->user->name }}
                                </a>
                                <div class="table-subtext">
                                    {{ $complaint->user->designation ?? 'Employee' }}
                                    @if ($complaint->user->mobile)
                                        <br>{{ $complaint->user->mobile }}
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-wrap">
                            {{ $complaint->subject ?: '-' }}
                            @if ($complaint->category)
                                <div class="table-subtext">{{ $complaint->category }}</div>
                            @endif
                        </td>
                        <td class="text-wrap">
                            {{ $complaint->message }}
                            @if ($complaint->admin_note)
                                <div class="table-subtext">Admin note: {{ $complaint->admin_note }}</div>
                            @endif
                        </td>
                        <td>{{ ucfirst($complaint->priority) }}</td>
                        <td>
                            <span class="status-pill status-{{ $complaint->status }}">
                                {{ str_replace('_', ' ', $complaint->status) }}
                            </span>
                            @if ($complaint->resolved_at)
                                <div class="table-subtext">
                                    {{ $complaint->resolved_at->format('d M Y h:i A') }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <form class="inline-status-form" method="POST" action="{{ route('admin.complaints.update', $complaint) }}">
                                @csrf
                                @method('PATCH')
                                <select name="status" required>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected($complaint->status === $status)>
                                            {{ str_replace('_', ' ', ucfirst($status)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <textarea name="admin_note" placeholder="Admin note">{{ $complaint->admin_note }}</textarea>
                                <button class="btn" type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No complaints found for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $complaints->links() }}
    </div>
@endsection
