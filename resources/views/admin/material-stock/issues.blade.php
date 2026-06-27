@extends('admin.layouts.app')

@section('title', 'Material Issues | Admin Panel')
@section('headerTitle', 'Material Issues')
@section('headerSubtitle', 'Issued material records and stock movement history')

@section('content')
    <div class="page-header">
        <div>
            <h1>Material Issues</h1>
            <p>Review material issued to sites and latest stock movements.</p>
        </div>
    </div>

    @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <section class="card table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Material</th>
                        <th>Site</th>
                        <th>Quantity</th>
                        <th>Engineer</th>
                        <th>Issued By</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($issues as $issue)
                        <tr>
                            <td>{{ $issue->issued_at?->format('d M Y h:i A') }}</td>
                            <td>{{ $issue->material?->name }} {{ $issue->material?->unit ? '('.$issue->material->unit.')' : '' }}</td>
                            <td>{{ $issue->site?->name ?? 'Main Store' }}</td>
                            <td>{{ number_format($issue->issued_quantity, 2) }}</td>
                            <td>{{ $issue->request?->engineer?->name ?? '-' }}</td>
                            <td>{{ $issue->issuer?->name ?? session('admin_email', 'Admin') }}</td>
                            <td class="text-wrap">{{ $issue->remarks }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No issue entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="pagination">{{ $issues->links('admin.pagination') }}</div>

    <section class="card table-card" style="margin-top: 22px;">
        <div class="sheet-report-title">Latest Stock Movements</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Material</th>
                        <th>Site</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Balance After</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td>{{ $movement->created_at?->format('d M Y h:i A') }}</td>
                            <td>{{ $movement->material?->name }}</td>
                            <td>{{ $movement->site?->name ?? 'Main Store' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $movement->type)) }}</td>
                            <td>{{ number_format($movement->quantity, 2) }}</td>
                            <td>{{ number_format($movement->balance_after, 2) }}</td>
                            <td class="text-wrap">{{ $movement->remarks }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No stock movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
