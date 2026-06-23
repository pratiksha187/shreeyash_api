@extends('admin.layouts.app')

@section('title', 'DPR Details | Admin Panel')
@section('headerTitle', 'DPR Details')
@section('headerSubtitle', 'One complete engineer daily progress report')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $report->user?->name ?? 'Engineer' }} DPR</h1>
            <p>{{ $report->dpr_date?->format('d M Y') }} grouped daily progress report.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.dpr-reports.index') }}">Back to DPR Reports</a>
    </div>

    <section class="detail-grid">
        <div class="card detail-item">
            <span>Date</span>
            <strong>{{ $report->dpr_date?->format('d M Y') }}</strong>
        </div>
        <div class="card detail-item">
            <span>Engineer</span>
            <strong>{{ $report->user?->name ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>DPR Added</span>
            <strong>{{ $report->reports_count }}</strong>
        </div>
        <div class="card detail-item">
            <span>Hourly Entries</span>
            <strong>{{ $report->hours_count }}</strong>
        </div>
    </section>

    <section class="detail-grid">
        <div class="card detail-item">
            <span>Photos</span>
            <strong>{{ $report->photos_count }}</strong>
        </div>
        <div class="card detail-item">
            <span>Latest DPR</span>
            <strong>#{{ $report->id }}</strong>
        </div>
        <div class="card detail-item">
            <span>Latest Submitted</span>
            <strong>{{ $report->created_at?->format('d M Y h:i A') ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Mobile</span>
            <strong>{{ $report->user?->mobile ?? '-' }}</strong>
        </div>
    </section>

    <div class="card form-card report-filter">
        <section class="form-section">
            <h2 class="section-title">Day Summary</h2>
            <div class="form-grid">
                <div class="field">
                    <label>Site / Project</label>
                    <div class="text-wrap">{{ $report->site_project }}</div>
                </div>
                <div class="field">
                    <label>Engineer Details</label>
                    <div>
                        {{ $report->user?->designation ?? 'Engineer' }}
                        @if ($report->user?->mobile)
                            <br>{{ $report->user->mobile }}
                        @endif
                    </div>
                </div>
                <div class="field full">
                    <label>Work Summary</label>
                    <div class="text-wrap">{{ $report->work_summary }}</div>
                </div>
            </div>
        </section>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>DPR</th>
                    <th>Site / Project</th>
                    <th>Work Summary</th>
                    <th>Hourly Details</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $dailyReport)
                    <tr>
                        <td>
                            <strong>DPR #{{ $dailyReport->id }}</strong>
                            <div class="table-subtext">
                                {{ $dailyReport->hours_count }} entries, {{ $dailyReport->photos_count }} photos
                            </div>
                        </td>
                        <td class="text-wrap">{{ $dailyReport->site_project }}</td>
                        <td class="text-wrap">{{ $dailyReport->work_summary }}</td>
                        <td>
                            <div class="hour-list">
                                @forelse ($dailyReport->hours->sortBy('hour_number') as $hour)
                                    <div class="hour-item">
                                        <strong>
                                            Hour {{ $hour->hour_number }}
                                            - {{ \Carbon\Carbon::parse($hour->work_time)->format('h:i A') }}
                                        </strong>
                                        <p>{{ $hour->remark ?: '-' }}</p>

                                        @if ($hour->photos->isNotEmpty())
                                            <div class="thumb-grid">
                                                @foreach ($hour->photos as $photo)
                                                    <a href="{{ route('admin.dpr-reports.photo', $photo) }}" target="_blank">
                                                        <img class="thumb" src="{{ route('admin.dpr-reports.photo', $photo) }}" alt="DPR photo">
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="table-subtext">No hourly entries.</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            {{ $dailyReport->created_at?->format('d M Y h:i A') }}
                            <div class="table-subtext">{{ $dailyReport->updated_at?->format('d M Y h:i A') }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="5">No DPR entries found for this date.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
