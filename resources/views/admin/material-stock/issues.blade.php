@extends('admin.layouts.app')

@section('title', 'Material Issues | Admin Panel')
@section('bodyClass', 'material-issues-page')
@section('headerTitle', 'Material Issues')
@section('headerSubtitle', 'Issued material records and stock movement history')

@section('content')
    <style>
        .material-issues-page .main {
            max-width: none;
            overflow-x: hidden;
        }

        .material-issues-page .page-header {
            margin-bottom: 18px;
        }

        .material-issues-page .page-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .material-issues-page .stats-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .material-issues-page .stat-card {
            min-height: 92px;
            display: grid;
            align-content: center;
            padding: 18px 24px;
        }

        .material-issues-page .issue-panel {
            overflow: hidden;
            margin-bottom: 20px;
        }

        .material-issues-page .issue-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 22px;
            border-bottom: 1px solid var(--line);
            background: #ffffff;
        }

        .material-issues-page .issue-panel-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 22px;
            line-height: 1.25;
        }

        .material-issues-page .issue-panel-head p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .material-issues-page .issue-table-wrap {
            overflow-x: hidden;
            width: 100%;
        }

        .material-issues-page .issue-table {
            width: 100%;
            min-width: 0;
            table-layout: fixed;
        }

        .material-issues-page .issue-table th,
        .material-issues-page .issue-table td {
            padding: 12px 14px;
            vertical-align: middle;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .material-issues-page .issue-table th {
            background: #f8fafc;
            color: #526b8d;
            font-size: 12px;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .material-issues-page .issue-table tbody tr:hover td {
            background: #f8fbff;
        }

        .material-issues-page .col-sr { width: 5%; }
        .material-issues-page .col-date { width: 12%; }
        .material-issues-page .col-material { width: 14%; }
        .material-issues-page .col-site { width: 10%; }
        .material-issues-page .col-project { width: 14%; }
        .material-issues-page .col-qty { width: 8%; }
        .material-issues-page .col-person { width: 11%; }
        .material-issues-page .col-remarks { width: 15%; }

        .material-issues-page .movement-table .col-date { width: 12%; }
        .material-issues-page .movement-table .col-material { width: 14%; }
        .material-issues-page .movement-table .col-site { width: 10%; }
        .material-issues-page .movement-table .col-project { width: 14%; }
        .material-issues-page .movement-table .col-type { width: 10%; }
        .material-issues-page .movement-table .col-qty { width: 8%; }
        .material-issues-page .movement-table .col-balance { width: 10%; }
        .material-issues-page .movement-table .col-remarks { width: 17%; }

        .material-issues-page .stock-type-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e5f0fb;
            color: var(--brand-blue-dark);
            font-size: 12px;
            font-weight: 800;
            text-align: center;
        }

        .material-issues-page .stock-type-pill.issue {
            background: #fee2e2;
            color: #991b1b;
        }

        .material-issues-page .stock-type-pill.purchase {
            background: #dcfce7;
            color: #166534;
        }

        .material-issues-page .text-wrap {
            min-width: 0;
            max-width: none;
        }

        @media (max-width: 1100px) {
            .material-issues-page .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .material-issues-page .stats-grid {
                grid-template-columns: 1fr;
            }

            .material-issues-page .issue-panel-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .material-issues-page .page-actions,
            .material-issues-page .page-actions .btn {
                width: 100%;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>Material Issues</h1>
            <p>Review material issued to sites and latest stock movements.</p>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('admin.material-issues.download-stock') }}">Download Stock CSV</a>
        </div>
    </div>

    @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
    @if (session('error') || isset($error)) <div class="alert-error">{{ session('error') ?: $error }}</div> @endif
    @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <section class="stats-grid">
        <div class="card stat-card"><span>Total Issues</span><strong>{{ $summary['issues'] }}</strong></div>
        <div class="card stat-card"><span>Issued Quantity</span><strong>{{ number_format($summary['issued_quantity'], 2) }}</strong></div>
        <div class="card stat-card"><span>Stock Movements</span><strong>{{ $summary['movements'] }}</strong></div>
        <div class="card stat-card"><span>Stock Rows</span><strong>{{ $summary['stock_rows'] }}</strong></div>
    </section>

    <section class="card table-card issue-panel">
        <div class="issue-panel-head">
            <div>
                <h2>Issued Material</h2>
                <p>Material sent from store to site or engineer request.</p>
            </div>
        </div>
        <div class="table-wrap issue-table-wrap">
            <table class="issue-table">
                <colgroup>
                    <col class="col-sr">
                    <col class="col-date">
                    <col class="col-material">
                    <col class="col-site">
                    <col class="col-project">
                    <col class="col-qty">
                    <col class="col-person">
                    <col class="col-person">
                    <col class="col-remarks">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Date</th>
                        <th>Material</th>
                        <th>Site</th>
                        <th>Project / Task</th>
                        <th>Quantity</th>
                        <th>Engineer</th>
                        <th>Issued By</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($issues as $issue)
                        <tr>
                            <td><strong>{{ $issues->firstItem() + $loop->index }}</strong></td>
                            <td>{{ $issue->issued_at?->format('d M Y h:i A') }}</td>
                            <td>{{ $issue->material?->name }} {{ $issue->material?->unit ? '('.$issue->material->unit.')' : '' }}</td>
                            <td>{{ $issue->site?->name ?? 'Main Store' }}</td>
                            <td>
                                <strong>{{ $issue->project?->name ?? '-' }}</strong>
                                <div class="table-subtext">{{ $issue->task?->title ?? '-' }}</div>
                            </td>
                            <td>{{ number_format($issue->issued_quantity, 2) }}</td>
                            <td>{{ $issue->request?->engineer?->name ?? '-' }}</td>
                            <td>{{ $issue->issuer?->name ?? session('admin_email', 'Admin') }}</td>
                            <td class="text-wrap">{{ $issue->remarks }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9">No issue entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="pagination">{{ $issues->links('admin.pagination') }}</div>

    <section class="card table-card issue-panel">
        <div class="issue-panel-head">
            <div>
                <h2>Latest Stock Movements</h2>
                <p>Latest inward, outward, reversal, and adjustment entries.</p>
            </div>
            <a class="btn small" href="{{ route('admin.material-issues.download-stock') }}">Download Stock CSV</a>
        </div>
        <div class="table-wrap issue-table-wrap">
            <table class="issue-table movement-table">
                <colgroup>
                    <col class="col-sr">
                    <col class="col-date">
                    <col class="col-material">
                    <col class="col-site">
                    <col class="col-project">
                    <col class="col-type">
                    <col class="col-qty">
                    <col class="col-balance">
                    <col class="col-remarks">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Date</th>
                        <th>Material</th>
                        <th>Site</th>
                        <th>Project / Task</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Balance After</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php
                            $typeClass = str_contains($movement->type, 'issue') ? 'issue' : (str_contains($movement->type, 'purchase') ? 'purchase' : '');
                        @endphp
                        <tr>
                            <td><strong>{{ $loop->iteration }}</strong></td>
                            <td>{{ $movement->created_at?->format('d M Y h:i A') }}</td>
                            <td>{{ $movement->material?->name }} {{ $movement->material?->unit ? '('.$movement->material->unit.')' : '' }}</td>
                            <td>{{ $movement->site?->name ?? 'Main Store' }}</td>
                            <td>
                                <strong>{{ $movement->project?->name ?? '-' }}</strong>
                                <div class="table-subtext">{{ $movement->task?->title ?? '-' }}</div>
                            </td>
                            <td><span class="stock-type-pill {{ $typeClass }}">{{ ucwords(str_replace('_', ' ', $movement->type)) }}</span></td>
                            <td>{{ number_format($movement->quantity, 2) }}</td>
                            <td>{{ number_format($movement->balance_after, 2) }}</td>
                            <td class="text-wrap">{{ $movement->remarks }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9">No stock movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
