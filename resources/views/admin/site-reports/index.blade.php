@extends('admin.layouts.app')

@section('title', 'Site Reports | Admin Panel')
@section('headerTitle', 'Site Reports')
@section('headerSubtitle', 'Download complete site data in PDF or Word')

@section('content')
    <div class="page-header">
        <div>
            <h1>Site Reports</h1>
            <p>Select a site and date range to collect projects, tasks, labour, materials, DPR, challans, and vehicle data.</p>
        </div>
    </div>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.site-reports.index') }}">
        <section class="form-section">
            <h2 class="section-title">Report Filter</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="labour_site_id">Site</label>
                    <select id="labour_site_id" name="labour_site_id" required>
                        <option value="">Select site</option>
                        @foreach ($sites as $availableSite)
                            <option value="{{ $availableSite->id }}" @selected((string) $filters['labour_site_id'] === (string) $availableSite->id)>
                                {{ $availableSite->name }}{{ $availableSite->address ? ' - '.$availableSite->address : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="from_date">From Date</label>
                    <input id="from_date" name="from_date" type="date" value="{{ $filters['from_date'] }}">
                </div>

                <div class="field">
                    <label for="to_date">To Date</label>
                    <input id="to_date" name="to_date" type="date" value="{{ $filters['to_date'] }}">
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Show Report</button>
            @if ($site)
                <a class="btn secondary" href="{{ route('admin.site-reports.pdf', $filters) }}">Download PDF</a>
                <a class="btn secondary" href="{{ route('admin.site-reports.word', $filters) }}">Download Word</a>
            @endif
        </div>
    </form>

    @if (! $site)
        <div class="card" style="padding: 18px;">
            <strong>Select site to generate report.</strong>
        </div>
    @else
        <section class="stats-grid">
            <div class="card stat-card">
                <span>Projects</span>
                <strong>{{ $report['summary']['projects'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>Tasks</span>
                <strong>{{ $report['summary']['tasks'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>Labour Entries</span>
                <strong>{{ $report['summary']['labour_entries'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>Unique Labours</span>
                <strong>{{ $report['summary']['unique_labours'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>DPR Reports</span>
                <strong>{{ $report['summary']['dprs'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>Material Requests</span>
                <strong>{{ $report['summary']['material_requests'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>Challans</span>
                <strong>{{ $report['summary']['challans'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>Vehicle Entries</span>
                <strong>{{ $report['summary']['vehicle_entries'] }}</strong>
            </div>
        </section>

        <div class="page-header">
            <div>
                <h1>{{ $site->name }}</h1>
                <p>{{ $site->address ?: 'Site data' }} | {{ \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') }}</p>
            </div>
            <div class="actions">
                <a class="btn" href="{{ route('admin.site-reports.pdf', $filters) }}">Download PDF</a>
                <a class="btn secondary" href="{{ route('admin.site-reports.word', $filters) }}">Download Word</a>
            </div>
        </div>

        <div class="card table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Data Available</th>
                        <th>Included In Download</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Projects</td><td>{{ $report['projects']->count() }}</td><td>Yes</td></tr>
                    <tr><td>Assigned Tasks</td><td>{{ $report['tasks']->count() }}</td><td>Yes</td></tr>
                    <tr><td>Task Updates / Remarks</td><td>{{ $report['task_updates']->count() }}</td><td>Yes</td></tr>
                    <tr><td>Assigned Labours</td><td>{{ $report['assigned_labours']->count() }}</td><td>Yes</td></tr>
                    <tr><td>Labour Attendance</td><td>{{ $report['labour_attendances']->count() }}</td><td>Yes</td></tr>
                    <tr><td>Material Requests</td><td>{{ $report['material_requests']->count() }}</td><td>Yes</td></tr>
                    <tr><td>Material Issues</td><td>{{ $report['material_issues']->count() }}</td><td>Yes</td></tr>
                    <tr><td>DPR Reports</td><td>{{ $report['dprs']->count() }}</td><td>Yes</td></tr>
                    <tr><td>Challans</td><td>{{ $report['challans']->count() }}</td><td>Yes</td></tr>
                    <tr><td>Vehicle Entries</td><td>{{ $report['vehicle_logs']->count() }}</td><td>Yes</td></tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection
