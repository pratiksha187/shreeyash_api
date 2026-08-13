@extends('admin.layouts.app')

@section('title', 'Material Requests | Admin Panel')
@section('bodyClass', 'material-requests-page')
@section('headerTitle', 'Material Requests')
@section('headerSubtitle', 'Approve engineer material requests and issue stock to sites')

@section('content')
    <style>
        .material-requests-page .main {
            max-width: none;
            overflow-x: hidden;
        }

        .material-requests-page .stats-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .material-requests-page .stat-card {
            min-height: 92px;
            display: grid;
            align-content: center;
            padding: 18px 24px;
        }

        .material-requests-page .request-filter-card {
            padding: 22px 30px 24px;
            margin-bottom: 18px;
        }

        .material-requests-page .request-filter-grid {
            display: grid;
            grid-template-columns: minmax(150px, .9fr) minmax(190px, 1fr) minmax(230px, 1.15fr) 150px;
            gap: 18px 22px;
            align-items: end;
        }

        .material-requests-page .request-filter-grid .field {
            min-width: 0;
        }

        .material-requests-page .request-filter-grid .filter-action {
            display: flex;
            align-items: end;
        }

        .material-requests-page .request-filter-grid .filter-action .btn {
            width: 100%;
            min-height: 50px;
            justify-content: center;
        }

        .material-requests-page .requests-table-wrap {
            overflow-x: hidden;
            width: 100%;
        }

        .material-requests-page .requests-table {
            width: 100%;
            min-width: 0;
            table-layout: fixed;
        }

        .material-requests-page .requests-table th,
        .material-requests-page .requests-table td {
            padding: 10px 8px;
            vertical-align: middle;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .material-requests-page .requests-table th {
            font-size: 12px;
            letter-spacing: 0;
        }

        .material-requests-page .col-sr { width: 7%; }
        .material-requests-page .col-engineer { width: 16%; }
        .material-requests-page .col-project { width: 17%; }
        .material-requests-page .col-material { width: 15%; }
        .material-requests-page .col-requested { width: 14%; }
        .material-requests-page .col-available { width: 13%; }
        .material-requests-page .col-status { width: 10%; }
        .material-requests-page .col-action { width: 8%; }

        .material-requests-page .request-sr-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .material-requests-page .request-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            color: var(--brand-blue);
            font-size: 18px;
            font-weight: 900;
            line-height: 1;
        }

        .material-requests-page .request-toggle[aria-expanded="true"] {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }

        .material-requests-page .request-detail-row[hidden] {
            display: none;
        }

        .material-requests-page .request-detail-row td {
            padding: 0;
            background: #f8fbff;
            vertical-align: top;
        }

        .material-requests-page .request-detail-panel {
            display: grid;
            grid-template-columns: minmax(360px, 1.1fr) minmax(280px, .8fr) minmax(180px, .45fr);
            gap: 16px;
            padding: 18px 20px;
            border-top: 1px solid var(--line);
            align-items: start;
        }

        .material-requests-page .request-detail-card {
            display: grid;
            align-content: start;
            gap: 12px;
            min-width: 0;
        }

        .material-requests-page .request-detail-card h3 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            line-height: 1.25;
            text-align: center;
        }

        .material-requests-page .request-message-card {
            display: grid;
            align-content: center;
            min-height: 118px;
            padding: 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #fff;
            text-align: center;
        }

        .material-requests-page .vendor-comparison-list {
            display: grid;
            gap: 8px;
        }

        .material-requests-page .vendor-comparison-item {
            display: grid;
            gap: 4px;
            padding: 10px 12px;
            border: 1px solid #fde7c7;
            border-radius: 8px;
            background: #fffaf3;
        }

        .material-requests-page .vendor-comparison-item strong {
            color: #0f172a;
            font-size: 13px;
            line-height: 1.2;
        }

        .material-requests-page .vendor-comparison-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            color: #526584;
            font-size: 12px;
            font-weight: 800;
        }

        .material-requests-page .vendor-comparison-status {
            display: inline-flex;
            width: fit-content;
            padding: 4px 8px;
            border-radius: 999px;
            background: #fff1d6;
            color: #c2410c;
            font-size: 11px;
            font-weight: 900;
        }

        .material-requests-page .request-delete-card form {
            margin: 0;
        }

        .material-requests-page .request-delete-card .btn {
            width: 100%;
            justify-content: center;
        }

        .material-requests-page .requests-table th:last-child,
        .material-requests-page .requests-table td:last-child {
            text-align: center;
        }

        .material-requests-page .inline-status-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 9px;
            min-width: 0;
        }

        .material-requests-page .inline-status-form select,
        .material-requests-page .inline-status-form input,
        .material-requests-page .inline-status-form textarea {
            width: 100%;
            min-width: 0;
            min-height: 34px;
            padding: 7px 8px;
            font-size: 12px;
        }

        .material-requests-page .inline-status-form textarea {
            min-height: 46px;
            resize: vertical;
        }

        .material-requests-page .inline-status-form .btn.small {
            width: 100%;
            min-height: 34px;
            justify-content: center;
        }

        .material-requests-page .request-detail-card .btn.small,
        .material-requests-page .request-detail-card a.btn.small {
            width: 100%;
            min-height: 42px;
            justify-content: center;
        }

        .material-requests-page .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            max-width: 100%;
            white-space: normal;
            text-align: center;
        }

        @media (max-width: 1200px) {
            .material-requests-page .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .material-requests-page .request-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .material-requests-page .stats-grid,
            .material-requests-page .request-filter-grid {
                grid-template-columns: 1fr;
            }

            .material-requests-page .request-filter-card {
                padding: 22px;
            }

            .material-requests-page .request-detail-panel {
                grid-template-columns: 1fr;
            }
        }
    </style>

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

    <form class="card form-card report-filter request-filter-card" method="GET" action="{{ route('admin.material-requests.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filter</h2>
            <div class="request-filter-grid">
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
                    <label>Project</label>
                    <select name="project_id">
                        <option value="">All Projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((int) $selectedProjectId === (int) $project->id)>
                                {{ $project->code ? $project->code.' - ' : '' }}{{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field filter-action">
                    <button class="btn" type="submit">Show Requests</button>
                </div>
            </div>
        </section>
    </form>

    <div class="card table-card">
        <div class="table-wrap requests-table-wrap">
            <table class="requests-table">
                <colgroup>
                    <col class="col-sr">
                    <col class="col-engineer">
                    <col class="col-project">
                    <col class="col-material">
                    <col class="col-requested">
                    <col class="col-available">
                    <col class="col-status">
                    <col class="col-action">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Engineer</th>
                        <th>Project / Site</th>
                        <th>Material</th>
                        <th>Requested</th>
                        <th>Available</th>
                        <th>Status</th>
                        <th>Open</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $requestRow)
                        @php
                            $available = $availableByRequest[$requestRow->id] ?? 0;
                            $sourceStocks = $stockRowsByRequest[$requestRow->id] ?? collect();
                            $firstSourceStock = $sourceStocks->first();
                            $firstSourceAvailable = $firstSourceStock ? (float) $firstSourceStock->available_quantity : 0;
                            $remainingApproved = max(0, (float) $requestRow->approved_quantity - (float) $requestRow->issued_quantity);
                            $material = $requestRow->relationLoaded('material') ? $requestRow->material : null;
                            $vendorComparisons = $vendorComparisonsByRequest[$requestRow->id] ?? collect();
                            $detailId = 'request-detail-'.$requestRow->id;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $requests->firstItem() + $loop->index }}</strong>
                            </td>
                            <td>
                                <strong>{{ $requestRow->engineer?->name ?? '-' }}</strong>
                                <div class="table-subtext">{{ $requestRow->engineer?->mobile }}</div>
                            </td>
                            <td>
                                <strong>{{ $requestRow->project?->name ?? '-' }}</strong>
                                <div class="table-subtext">Task: {{ $requestRow->task?->title ?? '-' }}</div>
                                <div class="table-subtext">Site: {{ $requestRow->site?->name ?? $requestRow->site_project ?? 'Main Store' }}</div>
                            </td>
                            <td>
                                <strong>{{ $requestRow->material_name ?: $material?->name }}</strong>
                                <div class="table-subtext">{{ $material?->material_type ?? '-' }} | {{ $requestRow->unit ?: $material?->unit ?? '-' }}</div>
                            </td>
                            <td>
                                {{ number_format($requestRow->requested_quantity, 2) }} {{ $requestRow->unit ?: $material?->unit }}
                                <div class="table-subtext">Request: {{ $requestRow->request_date?->format('d M Y') ?? '-' }}</div>
                                <div class="table-subtext">Required: {{ $requestRow->required_by?->format('d M Y') ?? $requestRow->required_date?->format('d M Y') ?? '-' }}</div>
                                <div class="table-subtext">Priority: {{ ucfirst($requestRow->priority ?? 'normal') }}</div>
                                <div class="table-subtext">{{ $requestRow->purpose }}</div>
                            </td>
                            <td>
                                {{ number_format($available, 2) }} {{ $requestRow->unit ?: $material?->unit }}
                                <div class="table-subtext">Total all stores</div>
                                @if ($sourceStocks->isNotEmpty())
                                    <div class="table-subtext">
                                        @foreach ($sourceStocks->take(3) as $sourceStock)
                                            {{ $sourceStock->site?->name ?? 'Main Store' }}: {{ number_format($sourceStock->available_quantity, 2) }}@if (! $loop->last), @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td><span class="status-pill status-{{ $requestRow->status }}">{{ ucwords(str_replace('_', ' ', $requestRow->status)) }}</span></td>
                            <td>
                                <button class="btn small request-toggle" type="button" data-request-toggle="{{ $detailId }}" aria-expanded="false" aria-controls="{{ $detailId }}">+</button>
                            </td>
                        </tr>
                        <tr id="{{ $detailId }}" class="request-detail-row" hidden>
                            <td colspan="8">
                                <div class="request-detail-panel">
                                    <div class="request-detail-card">
                                        <h3>Approve / Reject</h3>
                                        <form class="inline-status-form" method="POST" action="{{ route('admin.material-requests.update', $requestRow) }}">
                                            @csrf
                                            @method('PATCH')
                                            @if ($materials->isNotEmpty())
                                                <select name="material_id">
                                                    <option value="">Auto / Keep material</option>
                                                    @foreach ($materials as $materialOption)
                                                        <option value="{{ $materialOption->id }}" @selected((int) $requestRow->material_id === (int) $materialOption->id)>
                                                            Link: {{ $materialOption->name }}{{ $materialOption->unit ? ' ('.$materialOption->unit.')' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif
                                            <select name="project_id">
                                                <option value="">No project link</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}" @selected((int) $requestRow->project_id === (int) $project->id)>
                                                        {{ $project->code ? $project->code.' - ' : '' }}{{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <select name="project_task_id">
                                                <option value="">No task link</option>
                                                @foreach ($projectTasks as $task)
                                                    <option value="{{ $task->id }}" @selected((int) $requestRow->project_task_id === (int) $task->id)>
                                                        {{ $task->boq_item_number ? $task->boq_item_number.' - ' : '' }}{{ $task->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <select name="status">
                                                <option value="pending" @selected($requestRow->status === 'pending')>Pending</option>
                                                <option value="approved" @selected($requestRow->status === 'approved')>Approved</option>
                                                <option value="partially_approved" @selected($requestRow->status === 'partially_approved')>Partially Approved</option>
                                                <option value="purchase_required" @selected($requestRow->status === 'purchase_required')>Purchase Required</option>
                                                <option value="rejected" @selected($requestRow->status === 'rejected')>Rejected</option>
                                                <option value="cancelled" @selected($requestRow->status === 'cancelled')>Cancelled</option>
                                            </select>
                                            <input name="approved_quantity" type="number" min="0" step="0.01" value="{{ number_format($requestRow->approved_quantity ?: $requestRow->requested_quantity, 2, '.', '') }}">
                                            <textarea name="admin_note" placeholder="Admin note">{{ $requestRow->admin_note }}</textarea>
                                            <button class="btn small" type="submit">Save</button>
                                        </form>
                                    </div>
                                    <div class="request-detail-card">
                                        <h3>Issue</h3>
                                        @if (in_array($requestRow->status, ['approved', 'partially_approved'], true) && $remainingApproved > 0 && $available > 0 && $requestRow->material_id)
                                            <form class="inline-status-form material-issue-form" method="POST" action="{{ route('admin.material-requests.issue', $requestRow) }}">
                                                @csrf
                                                <select name="issue_source_labour_site_id" class="issue-source-select">
                                                    @foreach ($sourceStocks as $sourceStock)
                                                        <option
                                                            value="{{ $sourceStock->labour_site_id }}"
                                                            data-available="{{ number_format($sourceStock->available_quantity, 2, '.', '') }}"
                                                        >
                                                            From {{ $sourceStock->site?->name ?? 'Main Store' }} ({{ number_format($sourceStock->available_quantity, 2) }} available)
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <input class="issue-quantity-input" name="issued_quantity" type="number" min="0.01" max="{{ number_format(min($remainingApproved, $firstSourceAvailable ?: $available), 2, '.', '') }}" step="0.01" value="{{ number_format(min($remainingApproved, $firstSourceAvailable ?: $available), 2, '.', '') }}" data-remaining="{{ number_format($remainingApproved, 2, '.', '') }}">
                                                <textarea name="remarks" placeholder="Issue remarks"></textarea>
                                                <button class="btn small" type="submit">Send / Issue Material</button>
                                            </form>
                                        @elseif (in_array($requestRow->status, ['approved', 'partially_approved'], true) && ! $requestRow->material_id)
                                            <div class="request-message-card">
                                                <span class="table-subtext">Typed material request. Add this material in Material Master, purchase/add stock, then issue.</span>
                                            </div>
                                            <a class="btn small" href="{{ route('admin.materials.index') }}">Add Material</a>
                                        @elseif (in_array($requestRow->status, ['approved', 'partially_approved'], true) && $remainingApproved > 0 && $available <= 0)
                                            <div class="request-message-card">
                                                <span class="table-subtext">No stock available for issue.</span>
                                            </div>
                                            <a class="btn small" href="{{ route('admin.product-purchases.index') }}">Create Purchase</a>
                                        @elseif ($requestRow->status === 'purchase_required')
                                            <a class="btn small" href="{{ route('admin.product-purchases.index') }}">Create Purchase</a>
                                        @else
                                            <div class="request-message-card">
                                                <span class="table-subtext">No issue action</span>
                                            </div>
                                        @endif

                                        <h3>Compare Vendor</h3>
                                        @if ($vendorComparisons->isNotEmpty())
                                            <div class="vendor-comparison-list">
                                                @foreach ($vendorComparisons as $comparison)
                                                    <div class="vendor-comparison-item">
                                                        <strong>{{ $comparison->selected_vendor ?: $comparison->vendor_names ?: '-' }}</strong>
                                                        <div class="vendor-comparison-meta">
                                                            <span>{{ $comparison->material_name }}</span>
                                                            <span>|</span>
                                                            <span>Amount {{ number_format((float) $comparison->quoted_amount, 2) }}</span>
                                                        </div>
                                                        <span class="vendor-comparison-status">
                                                            {{ $comparison->approval_status === 'approved' && $comparison->selected_vendor ? 'Fixed' : ucwords(str_replace('_', ' ', $comparison->approval_status)) }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="request-message-card">
                                                <span class="table-subtext">No vendor comparison added for this material.</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="request-detail-card request-delete-card">
                                        <h3>Delete</h3>
                                        @if ((float) $requestRow->issued_quantity > 0)
                                            <div class="request-message-card">
                                                <span class="table-subtext">Issued request cannot be deleted.</span>
                                            </div>
                                        @else
                                            <form method="POST" action="{{ route('admin.material-requests.destroy', $requestRow) }}" onsubmit="return confirm('Delete this material request?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn small danger" type="submit">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
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

    <script>
        document.querySelectorAll('[data-request-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const detailId = button.dataset.requestToggle;
                const detailRow = document.getElementById(detailId);

                if (!detailRow) {
                    return;
                }

                const isOpening = detailRow.hidden;
                detailRow.hidden = !isOpening;

                document.querySelectorAll(`[data-request-toggle="${detailId}"]`).forEach((toggle) => {
                    toggle.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
                    toggle.textContent = isOpening ? '-' : '+';
                });
            });
        });

        document.querySelectorAll('.material-issue-form').forEach((form) => {
            const source = form.querySelector('.issue-source-select');
            const quantity = form.querySelector('.issue-quantity-input');

            const applySourceLimit = () => {
                const selected = source?.selectedOptions[0];
                if (!selected || !quantity) {
                    return;
                }

                const available = parseFloat(selected.dataset.available || 0);
                const remaining = parseFloat(quantity.dataset.remaining || 0);
                const allowed = Math.min(available, remaining);
                quantity.max = allowed.toFixed(2);

                if (parseFloat(quantity.value || 0) > allowed || parseFloat(quantity.value || 0) <= 0) {
                    quantity.value = allowed.toFixed(2);
                }
            };

            source?.addEventListener('change', applySourceLimit);
            applySourceLimit();
        });
    </script>
@endsection
