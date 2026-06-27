@extends('admin.layouts.app')

@section('title', 'Material Requests | Admin Panel')
@section('headerTitle', 'Material Requests')
@section('headerSubtitle', 'Approve engineer material requests and issue stock to sites')

@section('content')
    <div class="page-header">
        <div>
            <h1>Material Requests</h1>
            <p>Check request quantity against available stock before issuing material.</p>
        </div>
    </div>

    @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
    @if (session('error') || isset($error)) <div class="alert-error">{{ session('error') ?: $error }}</div> @endif
    @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <section class="stats-grid">
        <div class="card stat-card"><span>Pending</span><strong>{{ $summary['pending'] }}</strong></div>
        <div class="card stat-card"><span>Purchase Required</span><strong>{{ $summary['purchase_required'] }}</strong></div>
        <div class="card stat-card"><span>Issued</span><strong>{{ $summary['issued'] }}</strong></div>
        <div class="card stat-card"><span>Current Page</span><strong>{{ $requests->count() }}</strong></div>
    </section>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.material-requests.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filter</h2>
            <div class="form-grid three">
                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Site</label>
                    <select name="labour_site_id">
                        <option value="">All Sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected((int) $selectedSiteId === (int) $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Show Requests</button>
                </div>
            </div>
        </section>
    </form>

    <div class="card table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Engineer</th>
                        <th>Site</th>
                        <th>Material</th>
                        <th>Requested</th>
                        <th>Available</th>
                        <th>Status</th>
                        <th>Approve / Reject</th>
                        <th>Issue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $requestRow)
                        @php
                            $available = $availableByRequest[$requestRow->id] ?? 0;
                            $remainingApproved = max(0, (float) $requestRow->approved_quantity - (float) $requestRow->issued_quantity);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $requestRow->engineer?->name ?? '-' }}</strong>
                                <div class="table-subtext">{{ $requestRow->engineer?->mobile }}</div>
                            </td>
                            <td>{{ $requestRow->site?->name ?? $requestRow->site_project ?? 'Main Store' }}</td>
                            <td>
                                <strong>{{ $requestRow->material_name ?: $requestRow->material?->name }}</strong>
                                <div class="table-subtext">{{ $requestRow->material?->material_type ?? '-' }} | {{ $requestRow->unit ?: $requestRow->material?->unit ?? '-' }}</div>
                            </td>
                            <td>
                                {{ number_format($requestRow->requested_quantity, 2) }} {{ $requestRow->unit ?: $requestRow->material?->unit }}
                                <div class="table-subtext">Request: {{ $requestRow->request_date?->format('d M Y') ?? '-' }}</div>
                                <div class="table-subtext">Required: {{ $requestRow->required_by?->format('d M Y') ?? $requestRow->required_date?->format('d M Y') ?? '-' }}</div>
                                <div class="table-subtext">Priority: {{ ucfirst($requestRow->priority ?? 'normal') }}</div>
                                <div class="table-subtext">{{ $requestRow->purpose }}</div>
                            </td>
                            <td>
                                {{ number_format($available, 2) }} {{ $requestRow->unit ?: $requestRow->material?->unit }}
                                <div class="table-subtext">All stock locations</div>
                            </td>
                            <td><span class="status-pill status-{{ $requestRow->status }}">{{ ucwords(str_replace('_', ' ', $requestRow->status)) }}</span></td>
                            <td>
                                <form class="inline-status-form" method="POST" action="{{ route('admin.material-requests.update', $requestRow) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status">
                                        <option value="approved">Approved</option>
                                        <option value="partially_approved">Partially Approved</option>
                                        <option value="purchase_required">Purchase Required</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <input name="approved_quantity" type="number" min="0" step="0.01" value="{{ number_format($requestRow->approved_quantity ?: $requestRow->requested_quantity, 2, '.', '') }}">
                                    <textarea name="admin_note" placeholder="Admin note">{{ $requestRow->admin_note }}</textarea>
                                    <button class="btn small" type="submit">Save</button>
                                </form>
                            </td>
                            <td>
                                @if (in_array($requestRow->status, ['approved', 'partially_approved'], true) && $remainingApproved > 0)
                                    <form class="inline-status-form" method="POST" action="{{ route('admin.material-requests.issue', $requestRow) }}">
                                        @csrf
                                        <select name="issue_source_labour_site_id">
                                            <option value="">From Main Store</option>
                                            @foreach ($sites as $site)
                                                <option value="{{ $site->id }}">From {{ $site->name }}</option>
                                            @endforeach
                                        </select>
                                        <input name="issued_quantity" type="number" min="0.01" max="{{ min($remainingApproved, $available) }}" step="0.01" value="{{ number_format(min($remainingApproved, $available), 2, '.', '') }}">
                                        <textarea name="remarks" placeholder="Issue remarks"></textarea>
                                        <button class="btn small" type="submit">Issue Material</button>
                                    </form>
                                @elseif ($requestRow->status === 'purchase_required')
                                    <a class="btn small" href="{{ route('admin.product-purchases.index') }}">Create Purchase</a>
                                @else
                                    <span class="table-subtext">No issue action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No material requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination">{{ $requests->links('admin.pagination') }}</div>
@endsection
