@extends('admin.layouts.app')

@section('title', 'Machinery Diesel | Admin Panel')
@section('bodyClass', 'machinery-diesel-page')
@section('headerTitle', 'Machinery Diesel')
@section('headerSubtitle', 'Machine-wise diesel issue and balance sheet')

@section('content')
    <style>
        .machinery-diesel-page .main {
            max-width: none;
        }

        .machinery-diesel-table {
            min-width: 1680px;
        }

        .machinery-diesel-table th,
        .machinery-diesel-table td {
            padding: 9px 10px;
            text-align: center;
            vertical-align: middle;
            white-space: normal;
        }

        .machinery-diesel-table th {
            background: #d9ead3;
            color: #0f172a;
            font-size: 12px;
            letter-spacing: 0;
            text-transform: none;
        }

        .machinery-diesel-table .number-cell {
            white-space: nowrap;
        }

        .machinery-form-card {
            margin-bottom: 22px;
        }
    </style>

    <div class="page-header">
        <div>
            <h1>Machinery Diesel Sheet</h1>
            <p>Entries are calculated automatically after saving.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.machinery-diesel-logs.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filter</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="date">Date</label>
                    <input id="date" name="date" type="date" value="{{ old('date', $selectedDate) }}">
                </div>
                <div class="field">
                    <label for="month">Month</label>
                    <input id="month" name="month" type="month" value="{{ old('month', $selectedMonth) }}">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Show Sheet</button>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Machinery</span>
            <strong>{{ $summary['machinery_count'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Issued</span>
            <strong>{{ number_format($summary['actual_issued'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Expected Used</span>
            <strong>{{ number_format($summary['expected_consumption'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Closing Balance</span>
            <strong>{{ number_format($summary['closing_balance'], 2) }}</strong>
        </div>
    </section>

    <form class="card form-card machinery-form-card" method="POST" action="{{ route('admin.machinery-diesel-logs.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add / Update Entry</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="issue_date">Date</label>
                    <input id="issue_date" name="issue_date" type="date" value="{{ old('issue_date', $selectedDate ?? now()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label for="machinery">Machinery</label>
                    <input id="machinery" name="machinery" type="text" value="{{ old('machinery') }}" placeholder="Poclain 210 JCB" required>
                </div>
                <div class="field">
                    <label for="labour_site_id">Site</label>
                    <select id="labour_site_id" name="labour_site_id">
                        <option value="">No site</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected((string) old('labour_site_id') === (string) $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="minimum_stock_ltr">Minimum Stock (L)</label>
                    <input id="minimum_stock_ltr" name="minimum_stock_ltr" type="number" min="0" step="0.01" value="{{ old('minimum_stock_ltr', 0) }}">
                </div>
                <div class="field">
                    <label for="daily_diesel_for_8hr_ltr">Daily Diesel for 8 Hr (L)</label>
                    <input id="daily_diesel_for_8hr_ltr" name="daily_diesel_for_8hr_ltr" type="number" min="0" step="0.01" value="{{ old('daily_diesel_for_8hr_ltr', 0) }}">
                </div>
                <div class="field">
                    <label for="yesterday_balance_ltr">Yesterday Balance (L)</label>
                    <input id="yesterday_balance_ltr" name="yesterday_balance_ltr" type="number" min="0" step="0.01" value="{{ old('yesterday_balance_ltr', 0) }}">
                </div>
                <div class="field">
                    <label for="actual_diesel_issued_today_ltr">Actual Issued Today (L)</label>
                    <input id="actual_diesel_issued_today_ltr" name="actual_diesel_issued_today_ltr" type="number" min="0" step="0.01" value="{{ old('actual_diesel_issued_today_ltr', 0) }}">
                </div>
                <div class="field">
                    <label for="hours_worked">Hours Worked</label>
                    <input id="hours_worked" name="hours_worked" type="number" min="0" max="24" step="0.01" value="{{ old('hours_worked', 8) }}">
                </div>
                <div class="field">
                    <label for="evening_physical_balance_ltr">Evening Physical Balance (L)</label>
                    <input id="evening_physical_balance_ltr" name="evening_physical_balance_ltr" type="number" min="0" step="0.01" value="{{ old('evening_physical_balance_ltr') }}">
                </div>
                <div class="field">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Save Entry</button>
            </div>
        </section>
    </form>

    <section class="card table-wrap">
        <table class="sheet-table machinery-diesel-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Machinery</th>
                    <th>Minimum Stock (L)</th>
                    <th>Daily Diesel for 8 Hr (L)</th>
                    <th>Yesterday Balance (L)</th>
                    <th>Diesel to Issue Today (Calculated) (L)</th>
                    <th>Actual Diesel Issued Today (L)</th>
                    <th>Extra Diesel Issued (L)</th>
                    <th>Total Diesel Available After Filling (L)</th>
                    <th>Hours Worked</th>
                    <th>Expected Consumption (L)</th>
                    <th>Expected Closing Balance (L)</th>
                    <th>Evening Physical Balance (L)</th>
                    <th>Difference (L)</th>
                    <th>Diesel to Issue Tomorrow (L)</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="sheet-center">{{ $log->issue_date?->format('d-M-y') }}</td>
                        <td class="sheet-text">{{ $log->machinery }}</td>
                        <td class="number-cell">{{ number_format((float) $log->minimum_stock_ltr, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->daily_diesel_for_8hr_ltr, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->yesterday_balance_ltr, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->diesel_to_issue_today_ltr, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->actual_diesel_issued_today_ltr, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->extra_diesel_issued_ltr, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->total_diesel_available_after_filling_ltr, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->hours_worked, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->expected_consumption_ltr, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->expected_closing_balance_ltr, 2) }}</td>
                        <td class="number-cell">{{ $log->evening_physical_balance_ltr === null ? '-' : number_format((float) $log->evening_physical_balance_ltr, 2) }}</td>
                        <td class="number-cell">{{ $log->difference_ltr === null ? '-' : number_format((float) $log->difference_ltr, 2) }}</td>
                        <td class="number-cell">{{ number_format((float) $log->diesel_to_issue_tomorrow_ltr, 2) }}</td>
                        <td class="text-wrap">{{ $log->remarks ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="16">No machinery diesel entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
