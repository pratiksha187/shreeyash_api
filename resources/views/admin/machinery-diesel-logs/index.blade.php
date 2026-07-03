@extends('admin.layouts.app')

@section('title', 'Machinery Diesel | Admin Panel')
@section('bodyClass', 'machinery-diesel-page')
@section('headerTitle', 'Machinery Diesel')
@section('headerSubtitle', 'Machine-wise diesel issue and balance sheet')

@section('content')
    <style>
        .machinery-diesel-page .main {
            min-width: 0;
            max-width: none;
            padding: 24px 28px;
            overflow-x: hidden;
        }

        .machinery-diesel-page .content-shell {
            min-width: 0;
            overflow-x: hidden;
        }

        .machinery-diesel-page .page-header {
            margin-bottom: 16px;
        }

        .diesel-sheet-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 560px) minmax(0, 1fr);
            gap: 14px;
            align-items: end;
            margin-bottom: 16px;
            padding: 14px 16px;
            overflow: hidden;
        }

        .diesel-filter-grid {
            display: grid;
            grid-template-columns: minmax(150px, 210px) minmax(150px, 210px) auto;
            gap: 12px;
            align-items: end;
        }

        .diesel-summary-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(110px, 1fr));
            gap: 10px;
        }

        .diesel-summary-item {
            min-height: 58px;
            padding: 10px 12px;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            background: #f8fafc;
        }

        .diesel-summary-item span {
            display: block;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .diesel-summary-item strong {
            display: block;
            margin-top: 6px;
            color: #0f172a;
            font-size: 20px;
            line-height: 1;
        }

        .diesel-entry-card {
            margin-bottom: 16px;
            padding: 16px;
            overflow: hidden;
        }

        .diesel-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #d6a24c;
        }

        .diesel-section-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            line-height: 1.2;
        }

        .diesel-entry-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(150px, 1fr));
            gap: 12px;
            align-items: end;
        }

        .diesel-field {
            min-width: 0;
        }

        .diesel-field label {
            display: block;
            margin-bottom: 6px;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.2;
        }

        .diesel-field input,
        .diesel-field select,
        .diesel-field textarea {
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #fff;
            color: #0f172a;
            font-size: 13px;
        }

        .diesel-field textarea {
            min-height: 38px;
            resize: vertical;
        }

        .diesel-field input:focus,
        .diesel-field select:focus,
        .diesel-field textarea:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
        }

        .diesel-save-field {
            display: flex;
            align-items: end;
            height: 100%;
        }

        .diesel-save-field .btn {
            width: 100%;
        }

        .diesel-sheet-card {
            overflow: hidden;
            border-color: #8da17f;
        }

        .diesel-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .machinery-diesel-table {
            width: 100%;
            min-width: 1720px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .machinery-diesel-table th,
        .machinery-diesel-table td {
            border: 1px solid #64748b;
            padding: 8px 7px;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.22;
            text-align: center;
            vertical-align: middle;
            white-space: normal;
        }

        .machinery-diesel-table th {
            background: #d9ead3;
            color: #0f172a;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: none;
        }

        .machinery-diesel-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .machinery-diesel-table tbody tr:hover td {
            background: #eff6ff;
        }

        .machinery-diesel-table .number-cell {
            white-space: nowrap;
        }

        .machinery-diesel-table .sheet-text {
            min-width: 140px;
            text-align: left;
        }

        .machinery-diesel-table .empty {
            padding: 14px;
            font-size: 14px;
        }

        .diesel-action-link {
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
        }

        .diesel-action-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 1500px) {
            .diesel-sheet-toolbar {
                grid-template-columns: 1fr;
            }

            .diesel-entry-grid {
                grid-template-columns: repeat(3, minmax(150px, 1fr));
            }

            .diesel-save-field {
                align-items: stretch;
            }
        }

        @media (max-width: 760px) {
            .machinery-diesel-page .main {
                padding: 18px;
            }

            .diesel-filter-grid,
            .diesel-summary-strip,
            .diesel-entry-grid {
                grid-template-columns: 1fr;
            }
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

    <section class="card diesel-sheet-toolbar">
        <form class="diesel-filter-grid" method="GET" action="{{ route('admin.machinery-diesel-logs.index') }}">
                <div class="diesel-field">
                    <label for="date">Date</label>
                    <input id="date" name="date" type="date" value="{{ old('date', $selectedDate) }}">
                </div>
                <div class="diesel-field">
                    <label for="month">Month</label>
                    <input id="month" name="month" type="month" value="{{ old('month', $selectedMonth) }}">
                </div>
                <div class="diesel-save-field">
                    <button class="btn" type="submit">Show Sheet</button>
                </div>
        </form>

        <div class="diesel-summary-strip">
            <div class="diesel-summary-item">
                <span>Machinery</span>
                <strong>{{ $summary['machinery_count'] }}</strong>
            </div>
            <div class="diesel-summary-item">
                <span>Issued</span>
                <strong>{{ number_format($summary['actual_issued'], 2) }}</strong>
            </div>
            <div class="diesel-summary-item">
                <span>Expected Used</span>
                <strong>{{ number_format($summary['expected_consumption'], 2) }}</strong>
            </div>
            <div class="diesel-summary-item">
                <span>Closing Balance</span>
                <strong>{{ number_format($summary['closing_balance'], 2) }}</strong>
            </div>
        </div>
    </section>

    @php
        $remarkValue = old('remarks', $editLog?->remarks);
        $autoRemarkValue = $editLog?->autoRemarks();
        $shouldAutoRemark = $remarkValue === null
            || $remarkValue === ''
            || ($autoRemarkValue && $remarkValue === $autoRemarkValue);
    @endphp

    <form id="machinery-diesel-entry-form" class="card diesel-entry-card" method="POST" action="{{ route('admin.machinery-diesel-logs.store') }}">
        @csrf
        <input type="hidden" name="log_id" value="{{ old('log_id', $editLog?->id) }}">
        <input id="remarks_auto" type="hidden" name="remarks_auto" value="{{ $shouldAutoRemark ? 1 : 0 }}">
        <div class="diesel-section-head">
            <h2>{{ $editLog ? 'Edit Entry' : 'Add / Update Entry' }}</h2>
            @if ($editLog)
                <a class="diesel-action-link" href="{{ route('admin.machinery-diesel-logs.index', ['date' => $editLog->issue_date?->toDateString()]) }}">Cancel Edit</a>
            @endif
        </div>
        <div class="diesel-entry-grid">
                <div class="diesel-field">
                    <label for="issue_date">Date</label>
                    <input id="issue_date" name="issue_date" type="date" value="{{ old('issue_date', $editLog?->issue_date?->toDateString() ?? $selectedDate ?? now()->toDateString()) }}" required>
                </div>
                <div class="diesel-field">
                    <label for="machinery">Machinery</label>
                    <select id="machinery" name="machinery" required>
                        <option value="">Select vehicle</option>
                        @if ($editLog && ! $vehicles->contains(fn ($vehicle) => trim($vehicle->vehicle_number . ($vehicle->vehicle_type ? ' - ' . $vehicle->vehicle_type : '')) === $editLog->machinery))
                            <option value="{{ $editLog->machinery }}" selected>{{ $editLog->machinery }}</option>
                        @endif
                        @foreach ($vehicles as $vehicle)
                            @php
                                $vehicleLabel = trim($vehicle->vehicle_number . ($vehicle->vehicle_type ? ' - ' . $vehicle->vehicle_type : ''));
                            @endphp
                            <option value="{{ $vehicleLabel }}" @selected(old('machinery', $editLog?->machinery) === $vehicleLabel || old('machinery', $editLog?->machinery) === $vehicle->vehicle_number)>
                                {{ $vehicleLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="diesel-field">
                    <label for="labour_site_id">Site</label>
                    <select id="labour_site_id" name="labour_site_id">
                        <option value="">No site</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected((string) old('labour_site_id', $editLog?->labour_site_id) === (string) $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="diesel-field">
                    <label for="minimum_stock_ltr">Minimum Stock (L)</label>
                    <input id="minimum_stock_ltr" name="minimum_stock_ltr" type="number" min="0" step="0.01" value="{{ old('minimum_stock_ltr', $editLog?->minimum_stock_ltr ?? 0) }}">
                </div>
                <div class="diesel-field">
                    <label for="daily_diesel_for_8hr_ltr">Daily Diesel for 8 Hr (L)</label>
                    <input id="daily_diesel_for_8hr_ltr" name="daily_diesel_for_8hr_ltr" type="number" min="0" step="0.01" value="{{ old('daily_diesel_for_8hr_ltr', $editLog?->daily_diesel_for_8hr_ltr ?? 0) }}">
                </div>
                <div class="diesel-field">
                    <label for="yesterday_balance_ltr">Yesterday Balance (L)</label>
                    <input id="yesterday_balance_ltr" name="yesterday_balance_ltr" type="number" min="0" step="0.01" value="{{ old('yesterday_balance_ltr', $editLog?->yesterday_balance_ltr ?? 0) }}">
                </div>
                <div class="diesel-field">
                    <label for="actual_diesel_issued_today_ltr">Actual Issued Today (L)</label>
                    <input id="actual_diesel_issued_today_ltr" name="actual_diesel_issued_today_ltr" type="number" min="0" step="0.01" value="{{ old('actual_diesel_issued_today_ltr', $editLog?->actual_diesel_issued_today_ltr ?? 0) }}">
                </div>
                <div class="diesel-field">
                    <label for="hours_worked">Hours Worked</label>
                    <input id="hours_worked" name="hours_worked" type="number" min="0" max="24" step="0.01" value="{{ old('hours_worked', $editLog?->hours_worked ?? 8) }}">
                </div>
                <div class="diesel-field">
                    <label for="evening_physical_balance_ltr">Evening Physical Balance (L)</label>
                    <input id="evening_physical_balance_ltr" name="evening_physical_balance_ltr" type="number" min="0" step="0.01" value="{{ old('evening_physical_balance_ltr', $editLog?->evening_physical_balance_ltr) }}">
                </div>
                <div class="diesel-field">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="2" data-auto-remarks="{{ $shouldAutoRemark ? '1' : '0' }}">{{ $remarkValue }}</textarea>
                </div>
                <div class="diesel-save-field">
                <button class="btn" type="submit">{{ $editLog ? 'Update Entry' : 'Save Entry' }}</button>
                </div>
        </div>
    </form>

    <section class="card diesel-sheet-card">
        <div class="diesel-table-scroll">
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
                    <th>Action</th>
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
                        <td>
                            <a class="diesel-action-link" href="{{ route('admin.machinery-diesel-logs.index', ['date' => $selectedDate, 'month' => $selectedMonth, 'edit_id' => $log->id]) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="17">No machinery diesel entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>

    <script>
        (() => {
            const form = document.getElementById('machinery-diesel-entry-form');
            if (!form) {
                return;
            }

            const numberValue = (id) => parseFloat(document.getElementById(id)?.value || 0) || 0;
            const eveningInput = document.getElementById('evening_physical_balance_ltr');
            const remarks = document.getElementById('remarks');
            const remarksAuto = document.getElementById('remarks_auto');

            const autoRemark = () => {
                if (!eveningInput || eveningInput.value === '') {
                    return '';
                }

                const dailyDiesel = numberValue('daily_diesel_for_8hr_ltr');
                const yesterdayBalance = numberValue('yesterday_balance_ltr');
                const actualIssued = numberValue('actual_diesel_issued_today_ltr');
                const hoursWorked = numberValue('hours_worked');
                const expectedConsumption = dailyDiesel > 0 ? (dailyDiesel / 8) * hoursWorked : 0;
                const expectedClosing = yesterdayBalance + actualIssued - expectedConsumption;
                const difference = Math.round((numberValue('evening_physical_balance_ltr') - expectedClosing) * 100) / 100;

                if (difference > 0) {
                    return 'Extra Diesel Remaining';
                }

                if (difference < 0) {
                    return 'Diesel Missing';
                }

                return 'Diesel Balance OK';
            };

            const refreshRemark = () => {
                if (!remarks || remarks.dataset.autoRemarks !== '1') {
                    return;
                }

                remarks.value = autoRemark();
                if (remarksAuto) {
                    remarksAuto.value = '1';
                }
            };

            remarks?.addEventListener('input', () => {
                remarks.dataset.autoRemarks = remarks.value.trim() === '' ? '1' : '0';
                if (remarksAuto) {
                    remarksAuto.value = remarks.dataset.autoRemarks === '1' ? '1' : '0';
                }
            });

            [
                'daily_diesel_for_8hr_ltr',
                'yesterday_balance_ltr',
                'actual_diesel_issued_today_ltr',
                'hours_worked',
                'evening_physical_balance_ltr',
            ].forEach((id) => document.getElementById(id)?.addEventListener('input', refreshRemark));

            refreshRemark();
        })();
    </script>
@endsection
