@extends('admin.layouts.app')

@section('title', 'Diesel Purchase | Admin Panel')
@section('headerTitle', 'Daily Diesel Purchase')
@section('headerSubtitle', 'Monthly diesel purchase and site balance sheet')

@section('content')
    <style>
        .diesel-workspace {
            display: grid;
            gap: 22px;
        }

        .diesel-panel {
            padding: 18px;
        }

        .diesel-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .diesel-panel-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            line-height: 1.25;
        }

        .diesel-panel-head p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .diesel-table-scroll {
            overflow-x: auto;
        }

        .diesel-table {
            min-width: 760px;
            border-collapse: collapse;
        }

        .diesel-table th,
        .diesel-table td {
            padding: 9px 10px;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            font-size: 13px;
            text-align: center;
            white-space: nowrap;
        }

        .diesel-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: none;
        }

        .diesel-table input {
            width: 100%;
            min-width: 84px;
            min-height: 32px;
            padding: 5px 7px;
            border: 1px solid transparent;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            text-align: center;
        }

        .diesel-table input:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
        }

        .diesel-table input[readonly] {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 800;
        }

        .diesel-table .diesel-text-input {
            min-width: 110px;
        }

        .diesel-table .diesel-date {
            min-width: 96px;
            font-weight: 800;
        }

        .diesel-table .diesel-weekend td,
        .diesel-table .diesel-weekend input {
            background: #fef9c3;
        }

        .diesel-site-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(520px, 1fr));
            gap: 18px;
        }

        .diesel-site-card {
            min-width: 0;
        }

        .diesel-site-card .diesel-table {
            min-width: 620px;
        }

        .diesel-summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .diesel-summary-table th,
        .diesel-summary-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            text-align: left;
        }

        .diesel-summary-table th {
            color: #64748b;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .diesel-summary-table td:last-child,
        .diesel-summary-table th:last-child {
            text-align: right;
        }

        .diesel-actions {
            position: sticky;
            bottom: 0;
            z-index: 2;
            display: flex;
            justify-content: flex-end;
            padding: 14px 0 0;
            background: linear-gradient(to bottom, rgba(243, 246, 251, 0), #f3f6fb 35%);
        }

        @media (max-width: 640px) {
            .diesel-panel-head {
                flex-direction: column;
            }

            .diesel-site-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>{{ $monthLabel }} Daily Diesel Purchase</h1>
            <p>Purchase entry stays in one table. Site balances are separated below to keep the sheet easy to read.</p>
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
            <span>Total Sites</span>
            <strong>{{ $sites->count() }}</strong>
        </div>
        <div class="card stat-card">
            <span>Month</span>
            <strong>{{ $monthLabel }}</strong>
        </div>
    </section>

    @if ($sites->isNotEmpty())
        <section class="card diesel-panel">
            <div class="diesel-panel-head">
                <div>
                    <h2>Site Usage Summary</h2>
                    <p>Quick total of diesel used at each site for this month.</p>
                </div>
            </div>
            <table class="diesel-summary-table">
                <thead>
                    <tr>
                        <th>Site</th>
                        <th>Today Supply</th>
                        <th>Used</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['sites'] as $siteSummary)
                        <tr>
                            <td>{{ $siteSummary['name'] }}</td>
                            <td>{{ number_format($siteSummary['today_supply'], 2) }}</td>
                            <td>{{ number_format($siteSummary['used'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <form id="diesel-sheet-form" class="diesel-workspace" method="POST" action="{{ route('admin.diesel-purchases.monthly') }}">
        @csrf
        <input name="month" type="hidden" value="{{ $selectedMonth }}">

        @if ($sites->isEmpty())
            <div class="alert-error">Please add site names first from the Labour Attendance page. Diesel site balance tables will appear here automatically.</div>
        @endif

        <section class="card diesel-panel">
            <div class="diesel-panel-head">
                <div>
                    <h2>Purchase Details</h2>
                    <p>Daily challan, campar, diesel quantity, rate, and amount.</p>
                </div>
            </div>

            <div class="diesel-table-scroll">
                <table class="diesel-table">
                    <thead>
                        <tr>
                            <th>Sr No.</th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Challan</th>
                            <th>Campar</th>
                            <th>Diesel in Ltr</th>
                            <th>Rate</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $entryKey = $row['date']->toDateString();
                            @endphp
                            <tr data-purchase-row class="{{ $row['date']->isSunday() ? 'diesel-weekend' : '' }}">
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
                                    <input class="js-diesel-ltr" name="entries[{{ $entryKey }}][diesel_ltr]" type="number" min="0" step="0.01" value="{{ number_format($row['diesel_ltr'], 2, '.', '') }}">
                                </td>
                                <td>
                                    <input class="js-rate" name="entries[{{ $entryKey }}][rate]" type="number" min="0" step="0.01" value="{{ number_format($row['rate'], 2, '.', '') }}">
                                </td>
                                <td>
                                    <input class="js-amount" type="number" value="{{ number_format($row['amount'], 0, '.', '') }}" readonly>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="diesel-site-grid">
            @foreach ($sites as $site)
                <div class="card diesel-panel diesel-site-card">
                    <div class="diesel-panel-head">
                        <div>
                            <h2>{{ $site->name }}</h2>
                            <p>Opening balance, supply, usage, and closing balance.</p>
                        </div>
                    </div>

                    <div class="diesel-table-scroll">
                        <table class="diesel-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Opening</th>
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
                                        $siteRow = $row['sites'][$site->id];
                                    @endphp
                                    <tr data-site-row class="{{ $row['date']->isSunday() ? 'diesel-weekend' : '' }}">
                                        <td class="diesel-date">
                                            {{ $row['date']->format('d-m-Y') }}
                                            <input name="entries[{{ $entryKey }}][sites][{{ $site->id }}][labour_site_id]" type="hidden" value="{{ $site->id }}">
                                        </td>
                                        <td>
                                            <input class="js-site-opening" name="entries[{{ $entryKey }}][sites][{{ $site->id }}][opening_balance]" type="number" min="0" step="0.01" value="{{ number_format($siteRow['opening_balance'], 2, '.', '') }}">
                                        </td>
                                        <td>
                                            <input class="js-site-supply" name="entries[{{ $entryKey }}][sites][{{ $site->id }}][today_supply]" type="number" min="0" step="0.01" value="{{ number_format($siteRow['today_supply'], 2, '.', '') }}">
                                        </td>
                                        <td>
                                            <input class="js-site-total" type="number" value="{{ number_format($siteRow['total'], 2, '.', '') }}" readonly>
                                        </td>
                                        <td>
                                            <input class="js-site-used" name="entries[{{ $entryKey }}][sites][{{ $site->id }}][used]" type="number" min="0" step="0.01" value="{{ number_format($siteRow['used'], 2, '.', '') }}">
                                        </td>
                                        <td>
                                            <input class="js-site-balance" type="number" value="{{ number_format($siteRow['balance'], 2, '.', '') }}" readonly>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </section>

        <div class="diesel-actions">
            <button class="btn" type="submit">Save Diesel Sheet</button>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-purchase-row]').forEach((row) => {
            const recalculate = () => {
                const dieselLtr = parseFloat(row.querySelector('.js-diesel-ltr').value || 0);
                const rate = parseFloat(row.querySelector('.js-rate').value || 0);

                row.querySelector('.js-amount').value = Math.round(dieselLtr * rate);
            };

            row.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', recalculate);
                input.addEventListener('change', recalculate);
            });

            recalculate();
        });

        document.querySelectorAll('[data-site-row]').forEach((row) => {
            const recalculate = () => {
                const opening = parseFloat(row.querySelector('.js-site-opening').value || 0);
                const supply = parseFloat(row.querySelector('.js-site-supply').value || 0);
                const used = parseFloat(row.querySelector('.js-site-used').value || 0);

                row.querySelector('.js-site-total').value = Math.max(0, opening + supply).toFixed(2);
                row.querySelector('.js-site-balance').value = Math.max(0, opening + supply - used).toFixed(2);
            };

            row.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', recalculate);
                input.addEventListener('change', recalculate);
            });

            recalculate();
        });
    </script>
@endsection
