@extends('admin.layouts.app')

@section('title', 'Diesel Purchase | Admin Panel')
@section('headerTitle', 'Daily Diesel Purchase')
@section('headerSubtitle', 'Monthly diesel purchase and site balance sheet')

@section('content')
    <style>
        .diesel-sheet-table {
            min-width: 1420px;
        }

        .diesel-sheet-table th,
        .diesel-sheet-table td {
            border: 1px solid #111827;
            padding: 6px 7px;
            color: #020617;
            font-size: 13px;
            text-align: center;
            white-space: nowrap;
        }

        .diesel-sheet-table th {
            background: #fff;
            color: #020617;
            letter-spacing: 0;
            text-transform: none;
        }

        .diesel-sheet-table .diesel-title th {
            background: #ffff00;
            font-size: 18px;
            font-weight: 900;
        }

        .diesel-sheet-table .diesel-site-head {
            font-size: 16px;
            font-weight: 900;
        }

        .diesel-sheet-table .diesel-weekend td,
        .diesel-sheet-table .diesel-weekend input {
            background: #ffff00;
        }

        .diesel-sheet-table input {
            width: 100%;
            min-width: 74px;
            min-height: 28px;
            padding: 4px 5px;
            border: 0;
            border-radius: 0;
            background: transparent;
            font-size: 13px;
            text-align: center;
        }

        .diesel-sheet-table input[readonly] {
            background: #f8fafc;
            color: #020617;
            font-weight: 700;
        }

        .diesel-sheet-table .diesel-text-input {
            min-width: 90px;
        }

        .diesel-sheet-table .diesel-date {
            min-width: 88px;
        }
    </style>

    <div class="page-header">
        <div>
            <h1>{{ $monthLabel }} Daily Diesel Purchase</h1>
            <p>Enter daily purchase, rate, and Khanav/Khalapur supply usage. Amount and balances calculate automatically.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.diesel-purchases.index') }}">
        <section class="form-section">
            <h2 class="section-title">Month Filter</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="month">Month</label>
                    <input id="month" name="month" type="month" value="{{ old('month', $selectedMonth) }}">
                    @error('month')
                        <div class="error">{{ $message }}</div>
                    @enderror
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
            <span>Diesel in Ltr</span>
            <strong>{{ number_format($summary['diesel_ltr'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Amount</span>
            <strong>{{ number_format($summary['amount'], 0) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Khanav Used</span>
            <strong>{{ number_format($summary['khanav_used'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Khalapur Used</span>
            <strong>{{ number_format($summary['khalapur_used'], 2) }}</strong>
        </div>
    </section>

    <form id="diesel-sheet-form" class="card table-wrap" method="POST" action="{{ route('admin.diesel-purchases.monthly') }}">
        @csrf
        <input name="month" type="hidden" value="{{ $selectedMonth }}">

        <table class="diesel-sheet-table editable-sheet">
            <thead>
                <tr class="diesel-title">
                    <th colspan="18">{{ $monthLabel }} Daily Diesel Purchase</th>
                </tr>
                <tr>
                    <th rowspan="2">Sr No.</th>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Day</th>
                    <th rowspan="2">Challan</th>
                    <th rowspan="2">Campar</th>
                    <th rowspan="2">Diesel in Ltr</th>
                    <th rowspan="2">Rate</th>
                    <th rowspan="2">Amount</th>
                    <th class="diesel-site-head" colspan="5">Khanav</th>
                    <th class="diesel-site-head" colspan="5">Khalapur</th>
                </tr>
                <tr>
                    <th>Balance Khanav</th>
                    <th>Today Supply</th>
                    <th>Total</th>
                    <th>Used</th>
                    <th>Balance</th>
                    <th>Balance Khalapur</th>
                    <th>Today Supply</th>
                    <th>Total</th>
                    <th>Used</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $entryKey = $row['date']->toDateString();
                    @endphp
                    <tr data-diesel-row class="{{ $row['date']->isSunday() ? 'diesel-weekend' : '' }}">
                        <td>{{ $row['sr_no'] }}</td>
                        <td class="diesel-date">
                            {{ $row['date']->format('d-m-Y') }}
                            <input name="entries[{{ $entryKey }}][entry_date]" type="hidden" value="{{ $entryKey }}">
                        </td>
                        <td>{{ $row['date']->format('D') }}</td>
                        <td>
                            <input class="diesel-text-input" name="entries[{{ $entryKey }}][challan_no]" value="{{ $row['challan_no'] }}">
                        </td>
                        <td>
                            <input class="diesel-text-input" name="entries[{{ $entryKey }}][campar]" value="{{ $row['campar'] }}">
                        </td>
                        <td>
                            <input class="js-diesel-ltr sheet-number" name="entries[{{ $entryKey }}][diesel_ltr]" type="number" min="0" step="0.01" value="{{ number_format($row['diesel_ltr'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="js-rate sheet-number" name="entries[{{ $entryKey }}][rate]" type="number" min="0" step="0.01" value="{{ number_format($row['rate'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="js-amount sheet-number" type="number" value="{{ number_format($row['amount'], 0, '.', '') }}" readonly>
                        </td>
                        <td>
                            <input class="js-khanav-opening sheet-number" name="entries[{{ $entryKey }}][khanav_opening_balance]" type="number" min="0" step="0.01" value="{{ number_format($row['khanav_opening_balance'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="js-khanav-supply sheet-number" name="entries[{{ $entryKey }}][khanav_today_supply]" type="number" min="0" step="0.01" value="{{ number_format($row['khanav_today_supply'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="js-khanav-total sheet-number" type="number" value="{{ number_format($row['khanav_total'], 2, '.', '') }}" readonly>
                        </td>
                        <td>
                            <input class="js-khanav-used sheet-number" name="entries[{{ $entryKey }}][khanav_used]" type="number" min="0" step="0.01" value="{{ number_format($row['khanav_used'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="js-khanav-balance sheet-number" type="number" value="{{ number_format($row['khanav_balance'], 2, '.', '') }}" readonly>
                        </td>
                        <td>
                            <input class="js-khalapur-opening sheet-number" name="entries[{{ $entryKey }}][khalapur_opening_balance]" type="number" min="0" step="0.01" value="{{ number_format($row['khalapur_opening_balance'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="js-khalapur-supply sheet-number" name="entries[{{ $entryKey }}][khalapur_today_supply]" type="number" min="0" step="0.01" value="{{ number_format($row['khalapur_today_supply'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="js-khalapur-total sheet-number" type="number" value="{{ number_format($row['khalapur_total'], 2, '.', '') }}" readonly>
                        </td>
                        <td>
                            <input class="js-khalapur-used sheet-number" name="entries[{{ $entryKey }}][khalapur_used]" type="number" min="0" step="0.01" value="{{ number_format($row['khalapur_used'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="js-khalapur-balance sheet-number" type="number" value="{{ number_format($row['khalapur_balance'], 2, '.', '') }}" readonly>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="sheet-actions">
            <button class="btn" type="submit">Save Diesel Sheet</button>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-diesel-row]').forEach((row) => {
            const numberValue = (selector) => parseFloat(row.querySelector(selector).value || 0);
            const setValue = (selector, value) => {
                row.querySelector(selector).value = Math.max(0, value).toFixed(2);
            };

            const recalculate = () => {
                const dieselLtr = numberValue('.js-diesel-ltr');
                const rate = numberValue('.js-rate');
                const khanavOpening = numberValue('.js-khanav-opening');
                const khanavSupply = numberValue('.js-khanav-supply');
                const khanavUsed = numberValue('.js-khanav-used');
                const khalapurOpening = numberValue('.js-khalapur-opening');
                const khalapurSupply = numberValue('.js-khalapur-supply');
                const khalapurUsed = numberValue('.js-khalapur-used');

                row.querySelector('.js-amount').value = Math.round(dieselLtr * rate);
                setValue('.js-khanav-total', khanavOpening + khanavSupply);
                setValue('.js-khanav-balance', khanavOpening + khanavSupply - khanavUsed);
                setValue('.js-khalapur-total', khalapurOpening + khalapurSupply);
                setValue('.js-khalapur-balance', khalapurOpening + khalapurSupply - khalapurUsed);
            };

            row.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', recalculate);
                input.addEventListener('change', recalculate);
            });

            recalculate();
        });
    </script>
@endsection
