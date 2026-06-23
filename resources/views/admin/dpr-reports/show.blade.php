@extends('admin.layouts.app')

@section('title', 'DPR Details | Admin Panel')
@section('headerTitle', 'DPR Details')
@section('headerSubtitle', 'One complete engineer daily progress report')

@section('content')
    <div class="page-header">
        <div>
            <h1>DPR #{{ $report->id }}</h1>
            <p>{{ $report->dpr_date?->format('d M Y') }} daily progress report.</p>
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
            <span>Hourly Entries</span>
            <strong>{{ $report->hours_count }}</strong>
        </div>
        <div class="card detail-item">
            <span>Photos</span>
            <strong>{{ $report->photos_count }}</strong>
        </div>
    </section>

    <div class="card form-card report-filter">
        <section class="form-section">
            <h2 class="section-title">Report Summary</h2>
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
                    <th>Hour</th>
                    <th>Time</th>
                    <th>Remark</th>
                    <th>Photos</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report->hours->sortBy('hour_number') as $hour)
                    <tr>
                        <td>Hour {{ $hour->hour_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($hour->work_time)->format('h:i A') }}</td>
                        <td class="text-wrap">{{ $hour->remark ?: '-' }}</td>
                        <td>
                            @if ($hour->photos->isNotEmpty())
                                <div class="thumb-grid">
                                    @foreach ($hour->photos as $photo)
                                        <a href="{{ route('admin.dpr-reports.photo', $photo) }}" target="_blank">
                                            <img class="thumb" src="{{ route('admin.dpr-reports.photo', $photo) }}" alt="DPR photo">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <span class="table-subtext">No photos</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="4">No hourly entries found for this DPR.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
