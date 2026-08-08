@extends('admin.layouts.app')

@section('title', 'Safety Store | Admin Panel')
@section('bodyClass', 'safety-store-page')
@section('headerTitle', ($mode ?? 'store') === 'master' ? 'Safety Item Master' : 'Safety Store')
@section('headerSubtitle', ($mode ?? 'store') === 'master' ? 'Add PPE and safety store item master' : 'Safety purchase inward, requests, approval and issue')

@section('content')
    <style>
        .safety-store-page .main { max-width: none; overflow-x: hidden; }
        .safety-grid { display: grid; gap: 18px; }
        .safety-store-page .stats-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .safety-store-page .stat-card { min-height: 92px; display: grid; align-content: center; padding: 18px 24px; }
        .safety-two { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 18px; align-items: start; }
        .safety-panel { overflow: hidden; }
        .safety-panel-head { padding: 18px 22px; border-bottom: 1px solid var(--line); background: #fff; }
        .safety-panel-head h2 { margin: 0; color: #0f172a; font-size: 22px; line-height: 1.25; }
        .safety-panel-head p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
        .safety-panel-body { padding: 18px 22px; }
        .safety-form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px 16px; align-items: end; }
        .safety-form-grid .wide { grid-column: span 2; }
        .safety-form-grid .full { grid-column: 1 / -1; }
        .safety-table { width: 100%; table-layout: fixed; }
        .safety-table th, .safety-table td { padding: 10px 12px; vertical-align: middle; white-space: normal; overflow-wrap: anywhere; }
        .safety-table th { background: #f8fafc; color: #526b8d; font-size: 12px; letter-spacing: 0; text-transform: uppercase; }
        .safety-table tbody tr:hover td { background: #f8fbff; }
        .safety-actions { display: grid; grid-template-columns: minmax(0, 1fr) minmax(220px, .6fr); gap: 10px; align-items: start; }
        .safety-actions form { display: grid; gap: 8px; margin: 0; }
        .safety-actions select, .safety-actions input, .safety-actions textarea { width: 100%; min-width: 0; min-height: 34px; padding: 7px 8px; font-size: 12px; }
        .safety-actions textarea { min-height: 44px; }
        .safety-actions .btn { width: 100%; justify-content: center; }
        .safety-requests-wrap { overflow-x: hidden; }
        .safety-requests-table { min-width: 0; }
        .safety-requests-table .col-sr { width: 7%; }
        .safety-requests-table .col-item { width: 21%; }
        .safety-requests-table .col-work { width: 24%; }
        .safety-requests-table .col-requested { width: 20%; }
        .safety-requests-table .col-status { width: 14%; }
        .safety-requests-table .col-open { width: 14%; text-align: center; }
        .safety-request-toggle { width: 38px; min-width: 38px; height: 34px; padding: 0; justify-content: center; font-size: 18px; line-height: 1; }
        .safety-request-detail-row[hidden] { display: none; }
        .safety-request-detail-row td { padding: 0; background: #f8fbff; border-top: 1px solid var(--line); }
        .safety-request-detail-row:hover td { background: #f8fbff; }
        .safety-request-detail-panel { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr); gap: 16px; padding: 16px 20px 18px; }
        .safety-request-detail-card { display: grid; gap: 10px; min-width: 0; align-content: start; padding: 14px; border: 1px solid var(--line); border-radius: 8px; background: #fff; }
        .safety-request-detail-card h3 { margin: 0; color: #0f172a; font-size: 18px; line-height: 1.2; text-align: center; }
        .safety-request-detail-card form { display: grid; gap: 10px; margin: 0; }
        .safety-request-detail-card select, .safety-request-detail-card input, .safety-request-detail-card textarea { width: 100%; min-width: 0; min-height: 38px; }
        .safety-request-detail-card textarea { min-height: 62px; resize: vertical; }
        .safety-request-detail-card .btn { width: 100%; justify-content: center; min-height: 42px; }
        .safety-request-message-card { display: grid; min-height: 148px; align-content: center; justify-items: center; padding: 16px; border: 1px dashed #cbd5e1; border-radius: 8px; color: #526b8d; text-align: center; font-weight: 700; background: #f8fafc; }
        .safety-pill { display: inline-flex; justify-content: center; min-height: 28px; padding: 5px 10px; border-radius: 999px; background: #e5f0fb; color: var(--brand-blue-dark); font-size: 12px; font-weight: 800; }
        .safety-pill.pending { background: #fef3c7; color: #92400e; }
        .safety-pill.approved, .safety-pill.issued { background: #dcfce7; color: #166534; }
        .safety-pill.rejected, .safety-pill.cancelled { background: #fee2e2; color: #991b1b; }
        .safety-pill.purchase_required { background: #ffedd5; color: #9a3412; }
        @media (max-width: 1100px) { .safety-two, .safety-actions, .safety-request-detail-panel { grid-template-columns: 1fr; } .safety-store-page .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 760px) { .safety-form-grid, .safety-store-page .stats-grid { grid-template-columns: 1fr; } .safety-form-grid .wide { grid-column: span 1; } .safety-table th, .safety-table td { padding: 9px 8px; } }
    </style>

    <div class="page-header">
        <div>
            <h1>{{ ($mode ?? 'store') === 'master' ? 'Safety Item Master' : 'Safety Store' }}</h1>
            <p>{{ ($mode ?? 'store') === 'master' ? 'Add PPE and safety store items for use in safety requests and purchase inward.' : 'Purchase stock, approve site requests and issue PPE/tools to the required work side.' }}</p>
        </div>
    </div>

    @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
    @if (session('error') || isset($error)) <div class="alert-error">{{ session('error') ?: $error }}</div> @endif
    @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <section class="stats-grid">
        <div class="card stat-card"><span>Safety Items</span><strong>{{ $summary['items'] }}</strong></div>
        <div class="card stat-card"><span>Stock Rows</span><strong>{{ $summary['stock_rows'] }}</strong></div>
        <div class="card stat-card"><span>Pending Requests</span><strong>{{ $summary['pending'] }}</strong></div>
        <div class="card stat-card"><span>Issued Requests</span><strong>{{ $summary['issued'] }}</strong></div>
    </section>

    <div class="safety-grid">
        @if (($mode ?? 'store') === 'master')
            <section class="card safety-panel">
                <div class="safety-panel-head"><h2>Safety Item Master</h2><p>Add PPE and safety store items.</p></div>
                <div class="safety-panel-body">
                    <form class="safety-form-grid" method="POST" action="{{ route('admin.safety-store.items.store') }}">
                        @csrf
                        <div class="field"><label>Name</label><input name="name" required placeholder="Helmet, Shoes, Belt"></div>
                        <div class="field"><label>Category</label><input name="category" placeholder="PPE, Tool, Signage"></div>
                        <div class="field"><label>Unit</label><input name="unit" placeholder="Nos, Pair"></div>
                        <div class="field"><label>Minimum Stock</label><input name="minimum_stock" type="number" min="0" step="0.01" value="0"></div>
                        <div class="field"><button class="btn" type="submit">Save Item</button></div>
                    </form>
                </div>
            </section>

            <section class="card safety-panel">
                <div class="safety-panel-head"><h2>Safety Item List</h2><p>Items available for safety purchase and mobile requests.</p></div>
                <div class="table-wrap">
                    <table class="safety-table">
                        <thead><tr><th>Sr</th><th>Name</th><th>Category</th><th>Unit</th><th>Minimum</th><th>Total Stock</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($allItems as $item)
                                <tr>
                                    <td><strong>{{ $allItems->firstItem() + $loop->index }}</strong></td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->category ?? '-' }}</td>
                                    <td>{{ $item->unit ?? '-' }}</td>
                                    <td>{{ number_format($item->minimum_stock, 2) }}</td>
                                    <td>{{ number_format($item->stocks_sum_available_quantity ?? 0, 2) }}</td>
                                    <td><span class="status-pill {{ $item->is_active ? 'status-approved' : 'status-open' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7">No safety items added yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination">{{ $allItems->links('admin.pagination') }}</div>
            </section>
        @else
        <section class="card safety-panel">
                <div class="safety-panel-head"><h2>Purchase / Inward</h2><p>Purchase safety item and add stock to store/site.</p></div>
                <div class="safety-panel-body">
                    <form class="safety-form-grid" method="POST" action="{{ route('admin.safety-store.purchases.store') }}">
                        @csrf
                        <div class="field"><label>Item</label><select name="safety_item_id" required><option value="">Select Item</option>@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}{{ $item->unit ? ' - '.$item->unit : '' }}</option>@endforeach</select></div>
                        <div class="field"><label>Stock Site</label><select name="stock_labour_site_id"><option value="">Main Store</option>@foreach ($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach</select></div>
                        <div class="field"><label>Date</label><input name="purchase_date" type="date" value="{{ now()->toDateString() }}"></div>
                        <div class="field"><label>Supplier</label><input name="supplier_name"></div>
                        <div class="field"><label>Bill No</label><input name="bill_no"></div>
                        <div class="field"><label>Quantity</label><input name="quantity" type="number" min="0.01" step="0.01" required></div>
                        <div class="field"><label>Rate</label><input name="rate" type="number" min="0" step="0.01" value="0"></div>
                        <div class="field wide"><label>Remarks</label><input name="remarks"></div>
                        <div class="field"><button class="btn" type="submit">Save Purchase</button></div>
                    </form>
                </div>
        </section>

        <section class="card safety-panel">
            <div class="safety-panel-head"><h2>Safety Request</h2><p>Create site/work request for safety item, then approve and issue below.</p></div>
            <div class="safety-panel-body">
                <form class="safety-form-grid" method="POST" action="{{ route('admin.safety-store.requests.store') }}">
                    @csrf
                    <div class="field"><label>Item</label><select name="safety_item_id" required><option value="">Select Item</option>@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
                    <div class="field"><label>For Site</label><select name="labour_site_id"><option value="">Main Store</option>@foreach ($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Project</label><select name="project_id"><option value="">No project</option>@foreach ($projects as $project)<option value="{{ $project->id }}">{{ $project->code ? $project->code.' - ' : '' }}{{ $project->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Structure / Task</label><select name="project_task_id"><option value="">No task</option>@foreach ($projectTasks as $task)<option value="{{ $task->id }}">{{ $task->boq_item_number ? $task->boq_item_number.' - ' : '' }}{{ $task->title }}</option>@endforeach</select></div>
                    <div class="field"><label>Requested Qty</label><input name="requested_quantity" type="number" min="0.01" step="0.01" required></div>
                    <div class="field"><label>Requested By</label><input name="requested_by" placeholder="Engineer / Supervisor"></div>
                    <div class="field"><label>Priority</label><select name="priority"><option value="normal">Normal</option><option value="urgent">Urgent</option><option value="high">High</option></select></div>
                    <div class="field wide"><label>Purpose</label><input name="purpose" placeholder="For slab, excavation, height work"></div>
                    <div class="field"><button class="btn" type="submit">Save Request</button></div>
                </form>
            </div>
        </section>

        <section class="card safety-panel">
            <div class="safety-panel-head"><h2>Approve & Issue Requests</h2><p>Approve request and issue from available store/site stock.</p></div>
            <div class="table-wrap safety-requests-wrap">
                <table class="safety-table safety-requests-table">
                    <colgroup>
                        <col class="col-sr">
                        <col class="col-item">
                        <col class="col-work">
                        <col class="col-requested">
                        <col class="col-status">
                        <col class="col-open">
                    </colgroup>
                    <thead><tr><th>Sr</th><th>Item</th><th>Site / Work</th><th>Requested</th><th>Status</th><th>Open</th></tr></thead>
                    <tbody>
                        @forelse ($requests as $requestRow)
                            @php($remaining = max(0, (float) $requestRow->approved_quantity - (float) $requestRow->issued_quantity))
                            @php($detailId = 'safety-request-detail-'.$requestRow->id)
                            <tr>
                                <td><strong>{{ $requests->firstItem() + $loop->index }}</strong></td>
                                <td><strong>{{ $requestRow->item?->name }}</strong><div class="table-subtext">{{ $requestRow->item?->category ?? '-' }}</div></td>
                                <td><strong>{{ $requestRow->site?->name ?? 'Main Store' }}</strong><div class="table-subtext">{{ $requestRow->project?->name ?? '-' }}</div><div class="table-subtext">Task: {{ $requestRow->task?->title ?? '-' }}</div></td>
                                <td>
                                    {{ number_format($requestRow->requested_quantity, 2) }} {{ $requestRow->item?->unit }}
                                    <div class="table-subtext">By: {{ $requestRow->requested_by ?: ($requestRow->user?->name ?? '-') }}</div>
                                    <div class="table-subtext">Request: {{ $requestRow->request_date?->format('d M Y') ?? '-' }}</div>
                                    <div class="table-subtext">Required: {{ $requestRow->required_by?->format('d M Y') ?? '-' }}</div>
                                    <div class="table-subtext">Issued: {{ number_format($requestRow->issued_quantity, 2) }}</div>
                                </td>
                                <td><span class="safety-pill {{ $requestRow->status }}">{{ ucwords(str_replace('_', ' ', $requestRow->status)) }}</span></td>
                                <td class="col-open">
                                    <button class="btn small safety-request-toggle" type="button" data-safety-request-toggle="{{ $detailId }}" aria-controls="{{ $detailId }}" aria-expanded="false">+</button>
                                </td>
                            </tr>
                            <tr id="{{ $detailId }}" class="safety-request-detail-row" hidden>
                                <td colspan="6">
                                    <div class="safety-request-detail-panel">
                                        <div class="safety-request-detail-card">
                                            <h3>Approve / Reject</h3>
                                            <form method="POST" action="{{ route('admin.safety-store.requests.update', $requestRow) }}">
                                                @csrf @method('PATCH')
                                                <select name="status">@foreach (['pending','approved','partially_approved','purchase_required','rejected','cancelled'] as $status)<option value="{{ $status }}" @selected($requestRow->status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select>
                                                <input name="approved_quantity" type="number" min="0" step="0.01" value="{{ number_format($requestRow->approved_quantity ?: $requestRow->requested_quantity, 2, '.', '') }}">
                                                <textarea name="admin_note" placeholder="Admin note">{{ $requestRow->admin_note }}</textarea>
                                                <button class="btn small" type="submit">Save Approval</button>
                                            </form>
                                        </div>
                                        <div class="safety-request-detail-card">
                                            <h3>Issue</h3>
                                            @if (in_array($requestRow->status, ['approved', 'partially_approved'], true) && $remaining > 0)
                                                <form method="POST" action="{{ route('admin.safety-store.requests.issue', $requestRow) }}">
                                                    @csrf
                                                    <select name="issue_source_labour_site_id"><option value="">From Main Store</option>@foreach ($sites as $site)<option value="{{ $site->id }}">From {{ $site->name }}</option>@endforeach</select>
                                                    <input name="issued_quantity" type="number" min="0.01" max="{{ number_format($remaining, 2, '.', '') }}" step="0.01" value="{{ number_format($remaining, 2, '.', '') }}">
                                                    <textarea name="remarks" placeholder="Issue remarks"></textarea>
                                                    <button class="btn small" type="submit">Issue Safety Item</button>
                                                </form>
                                            @else
                                                <div class="safety-request-message-card">Approve first, then issue safety item.</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No safety requests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $requests->links('admin.pagination') }}</div>
        </section>

        <section class="safety-two">
            <div class="card safety-panel">
                <div class="safety-panel-head"><h2>Safety Stock</h2><p>Current available quantity by store/site.</p></div>
                <div class="table-wrap">
                    <table class="safety-table">
                        <thead><tr><th>Item</th><th>Site</th><th>Available</th><th>Minimum</th><th>Status</th></tr></thead>
                        <tbody>@forelse ($stocks as $stock)@php($low = (float) $stock->available_quantity <= (float) $stock->item->minimum_stock)<tr><td>{{ $stock->item?->name }}</td><td>{{ $stock->site?->name ?? 'Main Store' }}</td><td>{{ number_format($stock->available_quantity, 2) }}</td><td>{{ number_format($stock->item?->minimum_stock, 2) }}</td><td><span class="status-pill {{ $low ? 'status-open' : 'status-approved' }}">{{ $low ? 'Low' : 'Available' }}</span></td></tr>@empty<tr><td colspan="5">No safety stock found.</td></tr>@endforelse</tbody>
                    </table>
                </div>
            </div>
            <div class="card safety-panel">
                <div class="safety-panel-head"><h2>Recent Safety Issues</h2><p>Latest issued safety items.</p></div>
                <div class="table-wrap">
                    <table class="safety-table">
                        <thead><tr><th>Date</th><th>Item</th><th>Site</th><th>Qty</th><th>Remarks</th></tr></thead>
                        <tbody>@forelse ($issues as $issue)<tr><td>{{ $issue->issued_at?->format('d M Y h:i A') }}</td><td>{{ $issue->item?->name }}</td><td>{{ $issue->site?->name ?? 'Main Store' }}</td><td>{{ number_format($issue->issued_quantity, 2) }}</td><td>{{ $issue->remarks }}</td></tr>@empty<tr><td colspan="5">No safety issues yet.</td></tr>@endforelse</tbody>
                    </table>
                </div>
            </div>
        </section>
        @endif
    </div>

    <script>
        document.querySelectorAll('[data-safety-request-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const detail = document.getElementById(button.dataset.safetyRequestToggle);
                if (!detail) return;

                const isOpen = !detail.hidden;
                detail.hidden = isOpen;
                button.textContent = isOpen ? '+' : '-';
                button.setAttribute('aria-expanded', String(!isOpen));
            });
        });
    </script>
@endsection
