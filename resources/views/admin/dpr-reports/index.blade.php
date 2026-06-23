@extends('admin.layouts.app')

@section('title', 'DPR Reports | Admin Panel')
@section('headerTitle', 'DPR Reports')
@section('headerSubtitle', 'Engineer daily progress reports')

@section('content')
    <div class="page-header">
        <div>
            <h1>DPR Reports</h1>
            <p>Review every engineer DPR as one complete report with hourly entries and photos.</p>
        </div>
    </div>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.dpr-reports.index') }}">
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
                    <label for="user_id">Engineer</label>
                    <select id="user_id" name="user_id">
                        <option value="">All Engineers</option>
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
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Apply Filter</button>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total DPR</span>
            <strong>{{ $summary['total_reports'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Engineers</span>
            <strong>{{ $summary['engineers'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Hourly Entries</span>
            <strong>{{ $summary['hours'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Photos</span>
            <strong>{{ $summary['photos'] }}</strong>
        </div>
    </section>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Engineer</th>
                    <th>Site / Project</th>
                    <th>Work Summary</th>
                    <th>Entries</th>
                    <th>Photos</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>
                            <a class="table-link" href="{{ route('admin.dpr-reports.show', $report) }}">
                                {{ $report->dpr_date?->format('d M Y') }}
                            </a>
                            <div class="table-subtext">DPR #{{ $report->id }}</div>
                        </td>
                        <td>
                            @if ($report->user)
                                <a class="table-link" href="{{ route('admin.employees.show', $report->user) }}">
                                    {{ $report->user->name }}
                                </a>
                                <div class="table-subtext">
                                    {{ $report->user->designation ?? 'Engineer' }}
                                    @if ($report->user->mobile)
                                        <br>{{ $report->user->mobile }}
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-wrap">{{ $report->site_project }}</td>
                        <td class="text-wrap">{{ $report->work_summary }}</td>
                        <td>{{ $report->hours_count }}</td>
                        <td>{{ $report->photos_count }}</td>
                        <td>{{ $report->created_at?->format('d M Y h:i A') }}</td>
                        <td>
                            <a class="btn small" href="{{ route('admin.dpr-reports.show', $report) }}">View DPR</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="8">No DPR reports found for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $reports->links('admin.pagination') }}
    </div>
@endsection
