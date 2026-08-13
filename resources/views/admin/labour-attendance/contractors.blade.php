@extends('admin.layouts.app')

@section('title', 'Contractor Master | Admin Panel')
@section('headerTitle', 'Contractor Master')
@section('headerSubtitle', 'Create and manage contractors')
@section('bodyClass', 'contractor-page')

@section('content')
    <style>
        body.contractor-page {
            overflow-x: hidden;
        }

        body.contractor-page .main {
            max-width: 1280px;
        }

        body.contractor-page .page-header {
            align-items: flex-start;
        }

        body.contractor-page .page-header > div {
            min-width: 0;
        }

        body.contractor-page .page-header .btn {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .contractor-form {
            display: grid;
            gap: 28px;
            padding: 28px 32px;
            overflow: hidden;
        }

        .contractor-form .form-section {
            margin-bottom: 0;
        }

        .contractor-form .section-title {
            margin-bottom: 18px;
            font-size: 22px;
        }

        .contractor-form .form-grid {
            align-items: start;
            gap: 18px 24px;
        }

        .contractor-form .field {
            min-width: 0;
        }

        .contractor-form .actions {
            justify-content: flex-start;
            margin-top: 0;
        }

        .contractor-table-wrap {
            overflow-x: auto;
        }

        .contractor-table {
            min-width: 1180px;
        }

        .contractor-table-header,
        .contractor-table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
        }

        .contractor-table-header {
            border-bottom: 1px solid var(--border);
        }

        .contractor-table-footer {
            border-top: 1px solid var(--border);
        }

        .contractor-table-title {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .contractor-page-size {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .contractor-page-size select {
            width: auto;
            min-width: 76px;
            padding: 8px 28px 8px 10px;
        }

        .contractor-table th,
        .contractor-table td {
            padding: 14px 16px;
        }

        .contractor-summary {
            min-width: 190px;
            line-height: 1.45;
        }

        .contractor-summary strong {
            display: block;
        }

        .contractor-summary span {
            display: block;
            color: var(--muted);
            font-size: 12px;
        }

        .contractor-wide {
            grid-column: span 2;
        }

        .contractor-full {
            grid-column: 1 / -1;
        }

        @media (max-width: 760px) {
            .contractor-table-header,
            .contractor-table-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .contractor-page-size {
                justify-content: space-between;
            }

            .contractor-wide {
                grid-column: 1 / -1;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>Contractor Master</h1>
            <p>Maintain contractor agreements, work orders, measurements and RA billing details.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.labour-attendance.index') }}">View Attendance</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter contractor-form" method="POST" action="{{ route('admin.contractors.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Contractor</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="contractor_name">Contractor Name</label>
                    <input id="contractor_name" name="name" type="text" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="contractor_mobile">Mobile</label>
                    <input id="contractor_mobile" name="mobile" type="text" value="{{ old('mobile') }}">
                </div>
            </div>

            <h2 class="section-title">Contract & Work Order</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="agreement_no">Agreement No</label>
                    <input id="agreement_no" name="agreement_no" type="text" value="{{ old('agreement_no') }}">
                </div>
                <div class="field">
                    <label for="contract_no">Contract No</label>
                    <input id="contract_no" name="contract_no" type="text" value="{{ old('contract_no') }}">
                </div>
                <div class="field">
                    <label for="work_order_no">Work Order No</label>
                    <input id="work_order_no" name="work_order_no" type="text" value="{{ old('work_order_no') }}">
                </div>
                <div class="field">
                    <label for="contract_start_date">Start Date</label>
                    <input id="contract_start_date" name="contract_start_date" type="date" value="{{ old('contract_start_date') }}">
                </div>
                <div class="field">
                    <label for="contract_end_date">End Date</label>
                    <input id="contract_end_date" name="contract_end_date" type="date" value="{{ old('contract_end_date') }}">
                </div>
                <div class="field">
                    <label for="contract_value">Contract Value</label>
                    <input id="contract_value" name="contract_value" type="number" min="0" step="0.01" value="{{ old('contract_value') }}">
                </div>
            </div>

            <h2 class="section-title">Progress & Billing</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="progress_percent">Progress %</label>
                    <input id="progress_percent" name="progress_percent" type="number" min="0" max="100" step="0.01" value="{{ old('progress_percent') }}">
                </div>
                <div class="field">
                    <label for="last_measurement_date">Measurement Date</label>
                    <input id="last_measurement_date" name="last_measurement_date" type="date" value="{{ old('last_measurement_date') }}">
                </div>
                <div class="field">
                    <label for="last_ra_bill_no">RA Bill No</label>
                    <input id="last_ra_bill_no" name="last_ra_bill_no" type="text" value="{{ old('last_ra_bill_no') }}">
                </div>
                <div class="field">
                    <label for="last_ra_bill_amount">RA Bill Amount</label>
                    <input id="last_ra_bill_amount" name="last_ra_bill_amount" type="number" min="0" step="0.01" value="{{ old('last_ra_bill_amount') }}">
                </div>
                <div class="field">
                    <label for="retention_percent">Retention %</label>
                    <input id="retention_percent" name="retention_percent" type="number" min="0" max="100" step="0.01" value="{{ old('retention_percent') }}">
                </div>
                <div class="field">
                    <label for="recovery_amount">Recovery Amount</label>
                    <input id="recovery_amount" name="recovery_amount" type="number" min="0" step="0.01" value="{{ old('recovery_amount') }}">
                </div>
                <div class="field">
                    <label for="tds_percent">TDS %</label>
                    <input id="tds_percent" name="tds_percent" type="number" min="0" max="100" step="0.01" value="{{ old('tds_percent') }}">
                </div>
                <div class="field">
                    <label for="gst_percent">GST %</label>
                    <input id="gst_percent" name="gst_percent" type="number" min="0" max="100" step="0.01" value="{{ old('gst_percent') }}">
                </div>
                <div class="field">
                    <label for="net_payable_amount">Net Payable</label>
                    <input id="net_payable_amount" name="net_payable_amount" type="number" min="0" step="0.01" value="{{ old('net_payable_amount') }}">
                </div>
                <div class="field">
                    <label for="renewal_due_date">Renewal Due Date</label>
                    <input id="renewal_due_date" name="renewal_due_date" type="date" value="{{ old('renewal_due_date') }}">
                </div>
                <div class="field contractor-wide">
                    <label for="last_measurement_summary">Measurement Details</label>
                    <textarea id="last_measurement_summary" name="last_measurement_summary" rows="2">{{ old('last_measurement_summary') }}</textarea>
                </div>
                <div class="field contractor-full">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Add Contractor</button>
            </div>
        </section>
    </form>

    <div class="card">
        <div class="contractor-table-header">
            <p class="contractor-table-title">Contractors</p>
            <form class="contractor-page-size" method="GET" action="{{ route('admin.contractors.index') }}">
                <label for="per_page">Rows</label>
                <select id="per_page" name="per_page" onchange="this.form.submit()">
                    @foreach ([5, 10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="table-wrap contractor-table-wrap">
        <table class="contractor-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Contractor Name</th>
                    <th>Mobile</th>
                    <th>Contract</th>
                    <th>Progress</th>
                    <th>RA Billing</th>
                    <th>Renewal</th>
                    <th>Status</th>
                    <th>Labours</th>
                    <th>Attendance</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contractors as $contractor)
                    <tr>
                        <td>{{ $contractor->id }}</td>
                        <td>{{ $contractor->name }}</td>
                        <td>{{ $contractor->mobile ?? '-' }}</td>
                        <td class="contractor-summary">
                            <strong>{{ $contractor->work_order_no ?: ($contractor->contract_no ?: '-') }}</strong>
                            <span>Agreement: {{ $contractor->agreement_no ?: '-' }}</span>
                            <span>Value: {{ $contractor->contract_value !== null ? number_format((float) $contractor->contract_value, 2) : '-' }}</span>
                        </td>
                        <td class="contractor-summary">
                            <strong>{{ $contractor->progress_percent !== null ? number_format((float) $contractor->progress_percent, 2).'%' : '-' }}</strong>
                            <span>Measurement: {{ $contractor->last_measurement_date?->format('d M Y') ?? '-' }}</span>
                        </td>
                        <td class="contractor-summary">
                            <strong>{{ $contractor->last_ra_bill_no ?: '-' }}</strong>
                            <span>RA: {{ $contractor->last_ra_bill_amount !== null ? number_format((float) $contractor->last_ra_bill_amount, 2) : '-' }}</span>
                            <span>Net: {{ $contractor->net_payable_amount !== null ? number_format((float) $contractor->net_payable_amount, 2) : '-' }}</span>
                        </td>
                        <td>{{ $contractor->renewal_due_date?->format('d M Y') ?? '-' }}</td>
                        <td>
                            <span class="status-pill {{ $contractor->is_active ? 'status-approved' : 'status-rejected' }}">
                                {{ $contractor->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $contractor->labours_count }}</td>
                        <td>{{ $contractor->labour_attendances_count }}</td>
                        <td>{{ $contractor->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn small secondary" href="{{ route('admin.contractors.edit', $contractor) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.contractors.destroy', $contractor) }}" onsubmit="return confirm('Delete this contractor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="12">No contractors added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="contractor-table-footer">
            {{ $contractors->links('admin.pagination') }}
        </div>
    </div>
@endsection
