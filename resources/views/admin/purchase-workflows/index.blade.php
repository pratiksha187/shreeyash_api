@extends('admin.layouts.app')

@section('title', 'Purchase Workflow | Admin Panel')
@section('headerTitle', 'Purchase Workflow')
@section('headerSubtitle', 'Track requisition, indent, quotations, PO approval, and GRN posting')

@section('content')
    <style>
        .workflow-grid {
            display: grid;
            gap: 18px;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
        }

        .purchase-workflow-page {
            max-width: 100%;
            overflow-x: hidden;
        }

        .workflow-flow-wrap {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .workflow-flow {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
            align-items: stretch;
            min-width: 0;
        }

        .workflow-step {
            border: 1px solid #fed7aa;
            border-left: 4px solid #f97316;
            border-radius: 8px;
            background: #fff7ed;
            padding: 12px;
        }

        .workflow-step span {
            display: inline-grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #f97316;
            color: #fff;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .workflow-step strong {
            display: block;
            color: #0f172a;
            font-size: 14px;
        }

        .workflow-step small {
            display: block;
            margin-top: 4px;
            color: #526b91;
            font-weight: 700;
            line-height: 1.35;
        }

        .purchase-workflow-page .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            min-width: 0;
        }

        .purchase-workflow-page .form-card,
        .purchase-workflow-page .table-card,
        .purchase-workflow-page .stats-grid {
            max-width: 100%;
        }

        .purchase-workflow-page .form-grid.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .purchase-workflow-page label,
        .purchase-workflow-page input,
        .purchase-workflow-page select,
        .purchase-workflow-page textarea {
            min-width: 0;
            max-width: 100%;
        }

        .workflow-table-scroll {
            width: 100%;
            overflow: hidden;
        }

        .workflow-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .workflow-table th,
        .workflow-table td {
            border: 1px solid #d7e3f2;
            padding: 8px;
            vertical-align: top;
            font-size: 13px;
        }

        .workflow-table th {
            background: #f8fbff;
            color: #526b91;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .workflow-table input,
        .workflow-table select,
        .workflow-table textarea {
            width: 100%;
            min-height: 38px;
            border: 1px solid #c9d7e8;
            border-radius: 7px;
            padding: 8px 10px;
            background: #fff;
        }

        .workflow-table th:nth-child(1),
        .workflow-table td:nth-child(1),
        .workflow-table th:nth-child(2),
        .workflow-table td:nth-child(2),
        .workflow-table th:nth-child(3),
        .workflow-table td:nth-child(3) {
            width: 13%;
        }

        .workflow-table th:nth-child(4),
        .workflow-table td:nth-child(4) {
            width: 12%;
        }

        .workflow-table th:nth-child(5),
        .workflow-table td:nth-child(5),
        .workflow-table th:nth-child(6),
        .workflow-table td:nth-child(6),
        .workflow-table th:nth-child(7),
        .workflow-table td:nth-child(7),
        .workflow-table th:nth-child(8),
        .workflow-table td:nth-child(8) {
            width: 9%;
        }

        .workflow-table th:nth-child(9),
        .workflow-table td:nth-child(9) {
            width: 12%;
        }

        .workflow-table textarea {
            min-height: 66px;
            resize: vertical;
        }

        .workflow-actions {
            display: grid;
            gap: 8px;
            align-items: stretch;
        }

        .workflow-actions form {
            margin: 0;
        }

        .workflow-actions .btn {
            width: 100%;
            min-width: 0;
            padding-left: 10px;
            padding-right: 10px;
        }

        @media (max-width: 1100px) {
            .workflow-flow {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .purchase-workflow-page .form-grid.three {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .workflow-table {
                display: block;
            }

            .workflow-table thead,
            .workflow-table tbody,
            .workflow-table tr,
            .workflow-table th,
            .workflow-table td {
                display: block;
                width: 100% !important;
            }

            .workflow-table thead {
                display: none;
            }

            .workflow-table tr {
                border-bottom: 1px solid #d7e3f2;
            }
        }

        @media (max-width: 720px) {
            .workflow-flow,
            .purchase-workflow-page .form-grid.three {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="purchase-workflow-page workflow-grid">
        <div class="page-head">
            <div>
                <h1>Purchase Workflow</h1>
                <p>Material requisition to GRN posting with quotation and approval tracking.</p>
            </div>
        </div>

        @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert-error">{{ session('error') }}</div> @endif
        @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

        <section class="card form-card">
            <div class="workflow-flow-wrap">
                <div class="workflow-flow">
                    @foreach ([
                        ['Material Requisition', 'Site/team asks for required material.'],
                        ['Indent', 'Convert requirement into purchase indent.'],
                        ['Vendor Enquiry', 'Send enquiry to vendors.'],
                        ['Quotation Compare', 'Compare price, terms, and vendor.'],
                        ['PO Approval', 'Check approval status and limit.'],
                        ['GRN Posting', 'Record received material against PO.'],
                    ] as $index => $step)
                        <div class="workflow-step">
                            <span>{{ $index + 1 }}</span>
                            <strong>{{ $step[0] }}</strong>
                            <small>{{ $step[1] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="card stat-card"><span>Total Cases</span><strong>{{ $summary['total'] }}</strong></div>
            <div class="card stat-card"><span>Pending Approval</span><strong>{{ $summary['pending_approval'] }}</strong></div>
            <div class="card stat-card"><span>Approved</span><strong>{{ $summary['approved'] }}</strong></div>
            <div class="card stat-card"><span>GRN Posted</span><strong>{{ $summary['grn_posted'] }}</strong></div>
        </section>

        <form class="card form-card" method="POST" action="{{ route('admin.purchase-workflows.store') }}">
            @csrf
            <section class="form-section">
                <h2 class="section-title">Add Purchase Workflow</h2>
                <div class="form-grid three">
                    <label>Requisition No.<input name="requisition_no" value="{{ old('requisition_no') }}"></label>
                    <label>Requisition Date<input name="requisition_date" type="date" value="{{ old('requisition_date', now()->toDateString()) }}"></label>
                    <label>Indent No.<input name="indent_no" value="{{ old('indent_no') }}"></label>
                    <label>Material Name<input name="material_name" required value="{{ old('material_name') }}"></label>
                    <label>Unit<input name="unit" placeholder="CUM, Bag, Nos" value="{{ old('unit') }}"></label>
                    <label>Quantity<input name="quantity" type="number" min="0" step="0.001" value="{{ old('quantity', 0) }}"></label>
                    <label>Vendor Enquiry No.<input name="vendor_enquiry_no" value="{{ old('vendor_enquiry_no') }}"></label>
                    <label>Vendor Names<input name="vendor_names" placeholder="Vendor A, Vendor B" value="{{ old('vendor_names') }}"></label>
                    <label>Selected Vendor<input name="selected_vendor" value="{{ old('selected_vendor') }}"></label>
                    <label>Quoted Amount<input name="quoted_amount" type="number" min="0" step="0.01" value="{{ old('quoted_amount', 0) }}"></label>
                    <label>Approval Limit<input name="approval_limit" type="number" min="0" step="0.01" value="{{ old('approval_limit', 0) }}"></label>
                    <label>Approval Status
                        <select name="approval_status">
                            @foreach (['pending', 'approved', 'rejected', 'revision'] as $option)
                                <option value="{{ $option }}" @selected(old('approval_status', 'pending') === $option)>{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>PO No.<input name="po_no" value="{{ old('po_no') }}"></label>
                    <label>PO Date<input name="po_date" type="date" value="{{ old('po_date') }}"></label>
                    <label>PO Status
                        <select name="po_status">
                            @foreach (['draft', 'issued', 'closed', 'cancelled'] as $option)
                                <option value="{{ $option }}" @selected(old('po_status', 'draft') === $option)>{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>GRN No.<input name="grn_no" value="{{ old('grn_no') }}"></label>
                    <label>GRN Date<input name="grn_date" type="date" value="{{ old('grn_date') }}"></label>
                    <label>GRN Status
                        <select name="grn_status">
                            @foreach (['pending', 'partial', 'posted'] as $option)
                                <option value="{{ $option }}" @selected(old('grn_status', 'pending') === $option)>{{ ucfirst($option) }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label>Quotation Comparison
                    <textarea name="quotation_summary" rows="3" placeholder="Vendor A rate, Vendor B rate, terms, selected reason">{{ old('quotation_summary') }}</textarea>
                </label>
                <label>Remarks
                    <textarea name="remarks" rows="2">{{ old('remarks') }}</textarea>
                </label>
            </section>
            <button class="btn" type="submit">Add Workflow</button>
        </form>

        <form class="card form-card report-filter" method="GET" action="{{ route('admin.purchase-workflows.index') }}">
            <label>Search
                <input name="search" value="{{ $search }}" placeholder="Requisition, indent, material, vendor, PO, GRN">
            </label>
            <label>Status
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'approved', 'rejected', 'revision', 'draft', 'issued', 'closed', 'cancelled', 'partial', 'posted'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
        </form>

        <section>
            <h2>Purchase Workflow Register</h2>
            <p class="muted">Edit each purchase case as it moves from requisition to GRN.</p>
            <div class="card table-card workflow-table-scroll">
                <table class="workflow-table">
                    <thead>
                        <tr>
                            <th>Req / Indent</th>
                            <th>Material</th>
                            <th>Vendor Enquiry</th>
                            <th>Quotation Compare</th>
                            <th>PO Approval</th>
                            <th>PO Lifecycle</th>
                            <th>GRN Posting</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workflows as $workflow)
                            @php($formId = 'purchase-workflow-'.$workflow->id)
                            <tr>
                                <td>
                                    <input form="{{ $formId }}" name="requisition_no" value="{{ $workflow->requisition_no }}" placeholder="Req no">
                                    <input form="{{ $formId }}" name="requisition_date" type="date" value="{{ $workflow->requisition_date?->toDateString() }}">
                                    <input form="{{ $formId }}" name="indent_no" value="{{ $workflow->indent_no }}" placeholder="Indent no">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="material_name" required value="{{ $workflow->material_name }}">
                                    <input form="{{ $formId }}" name="unit" value="{{ $workflow->unit }}" placeholder="Unit">
                                    <input form="{{ $formId }}" name="quantity" type="number" min="0" step="0.001" value="{{ number_format((float) $workflow->quantity, 3, '.', '') }}">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="vendor_enquiry_no" value="{{ $workflow->vendor_enquiry_no }}" placeholder="Enquiry no">
                                    <textarea form="{{ $formId }}" name="vendor_names" placeholder="Vendor list">{{ $workflow->vendor_names }}</textarea>
                                </td>
                                <td>
                                    <textarea form="{{ $formId }}" name="quotation_summary">{{ $workflow->quotation_summary }}</textarea>
                                    <input form="{{ $formId }}" name="selected_vendor" value="{{ $workflow->selected_vendor }}" placeholder="Selected vendor">
                                    <input form="{{ $formId }}" name="quoted_amount" type="number" min="0" step="0.01" value="{{ number_format((float) $workflow->quoted_amount, 2, '.', '') }}">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="approval_limit" type="number" min="0" step="0.01" value="{{ number_format((float) $workflow->approval_limit, 2, '.', '') }}">
                                    <select form="{{ $formId }}" name="approval_status">
                                        @foreach (['pending', 'approved', 'rejected', 'revision'] as $option)
                                            <option value="{{ $option }}" @selected($workflow->approval_status === $option)>{{ ucfirst($option) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="po_no" value="{{ $workflow->po_no }}" placeholder="PO no">
                                    <input form="{{ $formId }}" name="po_date" type="date" value="{{ $workflow->po_date?->toDateString() }}">
                                    <select form="{{ $formId }}" name="po_status">
                                        @foreach (['draft', 'issued', 'closed', 'cancelled'] as $option)
                                            <option value="{{ $option }}" @selected($workflow->po_status === $option)>{{ ucfirst($option) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="grn_no" value="{{ $workflow->grn_no }}" placeholder="GRN no">
                                    <input form="{{ $formId }}" name="grn_date" type="date" value="{{ $workflow->grn_date?->toDateString() }}">
                                    <select form="{{ $formId }}" name="grn_status">
                                        @foreach (['pending', 'partial', 'posted'] as $option)
                                            <option value="{{ $option }}" @selected($workflow->grn_status === $option)>{{ ucfirst($option) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <textarea form="{{ $formId }}" name="remarks">{{ $workflow->remarks }}</textarea>
                                </td>
                                <td>
                                    <div class="workflow-actions">
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.purchase-workflows.update', $workflow) }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="btn small" type="submit">Update</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.purchase-workflows.destroy', $workflow) }}" onsubmit="return confirm('Delete this purchase workflow?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger small" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9">No purchase workflow added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $workflows->links('admin.pagination') }}</div>
        </section>
    </div>
@endsection
