@extends('admin.layouts.app')

@section('title', 'Purchase Orders | Admin Panel')
@section('headerTitle', 'Purchase Orders')
@section('headerSubtitle', 'Create multi-item PO and download printable purchase order')

@section('content')
    <style>
        .po-page {
            display: grid;
            gap: 18px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .po-form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px 18px;
        }

        .po-form-grid .wide {
            grid-column: span 2;
        }

        .po-items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .po-items th,
        .po-items td {
            border: 1px solid #d7e3f2;
            padding: 8px;
            vertical-align: top;
        }

        .po-items th {
            background: #f8fbff;
            color: #526b91;
            font-size: 12px;
            text-transform: uppercase;
        }

        .po-items input,
        .po-items select,
        .po-items textarea,
        .po-form-grid input,
        .po-form-grid select,
        .po-form-grid textarea {
            width: 100%;
            min-width: 0;
            border: 1px solid #c9d7e8;
            border-radius: 7px;
            padding: 9px 10px;
        }

        .po-items textarea,
        .po-form-grid textarea {
            resize: vertical;
            min-height: 68px;
        }

        .po-item-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .po-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .po-orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .po-orders-table th,
        .po-orders-table td {
            border-bottom: 1px solid #d7e3f2;
            padding: 12px;
            text-align: left;
        }

        .po-row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        @media (max-width: 1100px) {
            .po-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .po-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .po-form-grid,
            .po-summary {
                grid-template-columns: 1fr;
            }

            .po-form-grid .wide {
                grid-column: span 1;
            }
        }
    </style>

    <div class="po-page">
        <div class="page-head">
            <div>
                <h1>Purchase Orders</h1>
                <p>Create one PO with multiple item rows, auto totals, and printable PDF.</p>
            </div>
        </div>

        @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

        <form class="card form-card" method="POST" action="{{ route('admin.purchase-orders.store') }}" id="po-form">
            @csrf
            <section class="form-section">
                <h2 class="section-title">Create Purchase Order</h2>
                <div class="po-form-grid">
                    <label>Supplier Master
                        <select id="supplier-master-select">
                            <option value="">Select supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option
                                    value="{{ $supplier->id }}"
                                    data-name="{{ $supplier->name }}"
                                    data-address="{{ $supplier->address }}"
                                    data-gstin="{{ $supplier->gstin }}"
                                    data-tds-section="{{ $supplier->tds_section }}"
                                    data-tds-percent="{{ $supplier->tds_percent }}"
                                    data-e-invoice="{{ $supplier->e_invoice_applicable ? '1' : '0' }}"
                                    data-e-way-bill="{{ $supplier->e_way_bill_applicable ? '1' : '0' }}"
                                    data-vendor-reconciliation="{{ $supplier->vendor_reconciliation_status }}"
                                    data-auditor-export-note="{{ $supplier->auditor_export_note }}"
                                    data-dispatch="{{ $supplier->default_dispatched_through }}"
                                    data-destination="{{ $supplier->default_destination }}"
                                    data-terms="{{ $supplier->default_terms }}"
                                >{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>PO No.
                        <input name="po_no" value="{{ old('po_no', $nextPoNo) }}" placeholder="Auto if blank">
                    </label>
                    <label>PO Date
                        <input name="po_date" type="date" value="{{ old('po_date', now()->toDateString()) }}" required>
                    </label>
                    <label>Delivery Date
                        <input name="delivery_date" type="date" value="{{ old('delivery_date') }}">
                    </label>
                    <label>Supplier Name
                        <input id="supplier_name" name="supplier_name" value="{{ old('supplier_name') }}" required>
                    </label>
                    <label>Supplier GSTIN
                        <input id="supplier_gstin" name="supplier_gstin" value="{{ old('supplier_gstin') }}">
                    </label>
                    <label class="wide">Supplier Address
                        <textarea id="supplier_address" name="supplier_address">{{ old('supplier_address') }}</textarea>
                    </label>
                    <label>Supplier Ref.
                        <input name="supplier_ref" value="{{ old('supplier_ref') }}">
                    </label>
                    <label>TDS Section
                        <input id="supplier_tds_section" name="supplier_tds_section" value="{{ old('supplier_tds_section') }}">
                    </label>
                    <label>TDS %
                        <input id="tds_percent" class="po-tds" name="tds_percent" type="number" min="0" max="100" step="0.01" value="{{ old('tds_percent', 0) }}">
                    </label>
                    <label>E-Invoice
                        <select id="e_invoice_applicable" name="e_invoice_applicable">
                            <option value="0" @selected(old('e_invoice_applicable', '0') === '0')>No</option>
                            <option value="1" @selected(old('e_invoice_applicable') === '1')>Yes</option>
                        </select>
                    </label>
                    <label>E-Way Bill
                        <select id="e_way_bill_applicable" name="e_way_bill_applicable">
                            <option value="0" @selected(old('e_way_bill_applicable', '0') === '0')>No</option>
                            <option value="1" @selected(old('e_way_bill_applicable') === '1')>Yes</option>
                        </select>
                    </label>
                    <label>Vendor Reconciliation
                        <select id="vendor_reconciliation_status" name="vendor_reconciliation_status">
                            @foreach (['' => 'Select status', 'pending' => 'Pending', 'matched' => 'Matched', 'mismatch' => 'Mismatch', 'not_required' => 'Not Required'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('vendor_reconciliation_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Dispatched Through
                        <input id="dispatched_through" name="dispatched_through" value="{{ old('dispatched_through') }}">
                    </label>
                    <label>Destination
                        <input id="destination" name="destination" value="{{ old('destination') }}">
                    </label>
                    <label class="wide">Delivery Location
                        <textarea name="delivery_location">{{ old('delivery_location') }}</textarea>
                    </label>
                    <label class="wide">Auditor Export Note
                        <textarea id="auditor_export_note" name="auditor_export_note">{{ old('auditor_export_note') }}</textarea>
                    </label>
                    <label>Status
                        <select name="status">
                            @foreach (['draft', 'issued', 'closed', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected(old('status', 'issued') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section>

            <section class="form-section">
                <h2 class="section-title">PO Items</h2>
                <table class="po-items">
                    <thead>
                        <tr>
                            <th style="width: 5%">Sr.</th>
                            <th style="width: 34%">Item Description</th>
                            <th style="width: 12%">HSN Code</th>
                            <th style="width: 12%">Quantity</th>
                            <th style="width: 10%">Unit</th>
                            <th style="width: 12%">Rate</th>
                            <th style="width: 12%">Amount</th>
                            <th style="width: 3%"></th>
                        </tr>
                    </thead>
                    <tbody id="po-items-body">
                        @for ($i = 0; $i < 3; $i++)
                            <tr data-po-item>
                                <td class="po-sr">{{ $i + 1 }}</td>
                                <td><textarea name="items[{{ $i }}][item_description]" @required($i === 0)>{{ old("items.$i.item_description") }}</textarea></td>
                                <td><input name="items[{{ $i }}][hsn_code]" value="{{ old("items.$i.hsn_code") }}"></td>
                                <td><input class="po-qty" name="items[{{ $i }}][quantity]" type="number" min="0" step="0.001" value="{{ old("items.$i.quantity", 0) }}"></td>
                                <td>
                                    <select name="items[{{ $i }}][unit]">
                                        <option value="">Unit</option>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->name }}" @selected(old("items.$i.unit") === $unit->name)>{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="po-rate" name="items[{{ $i }}][rate]" type="number" min="0" step="0.01" value="{{ old("items.$i.rate", 0) }}"></td>
                                <td><input class="po-amount" type="number" value="0.00" readonly></td>
                                <td><button class="btn small secondary po-remove" type="button">-</button></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
                <div class="po-item-actions">
                    <button class="btn secondary" type="button" id="add-po-item">Add Item</button>
                </div>

                <div class="po-summary">
                    <label>Subtotal<input id="po-subtotal" type="number" value="0.00" readonly></label>
                    <label>CGST<input class="po-tax" name="cgst_amount" type="number" min="0" step="0.01" value="{{ old('cgst_amount', 0) }}"></label>
                    <label>SGST<input class="po-tax" name="sgst_amount" type="number" min="0" step="0.01" value="{{ old('sgst_amount', 0) }}"></label>
                    <label>IGST<input class="po-tax" name="igst_amount" type="number" min="0" step="0.01" value="{{ old('igst_amount', 0) }}"></label>
                </div>
                <div class="po-summary">
                    <label>Total Amount<input id="po-total" type="number" value="0.00" readonly></label>
                    <label>TDS Amount<input id="po-tds-amount" type="number" value="0.00" readonly></label>
                    <label>Net Payable<input id="po-net-payable" type="number" value="0.00" readonly></label>
                </div>
            </section>

            <section class="form-section">
                <label>Terms & Conditions
                    <textarea id="terms" name="terms">Material Supply: Supplier shall supply and deliver material as per agreed specification and quantity.
Payment Terms: Payment shall be made after material receipt as per agreed terms.</textarea>
                </label>
            </section>

            <button class="btn" type="submit">Create PO</button>
        </form>

        <section>
            <h2>Purchase Order List</h2>
            <div class="card table-card">
                <table class="po-orders-table">
                    <thead>
                        <tr>
                            <th>PO No.</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Net Payable</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><strong>{{ $order->po_no }}</strong></td>
                                <td>{{ $order->po_date?->format('d/m/Y') }}</td>
                                <td>{{ $order->supplier_name }}</td>
                                <td>{{ $order->items_count }}</td>
                                <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                                <td>{{ number_format((float) ($order->net_payable_amount ?: $order->total_amount), 2) }}</td>
                                <td><span class="status-pill status-approved">{{ ucfirst($order->status) }}</span></td>
                                <td>
                                    <div class="po-row-actions">
                                        <a class="btn small" href="{{ route('admin.purchase-orders.download', $order) }}">Download PDF</a>
                                        <form method="POST" action="{{ route('admin.purchase-orders.destroy', $order) }}" onsubmit="return confirm('Delete this PO?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger small" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No purchase orders created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $orders->links('admin.pagination') }}</div>
        </section>
    </div>

    <script>
        const body = document.getElementById('po-items-body');
        const supplierMasterSelect = document.getElementById('supplier-master-select');
        const unitOptions = @json($units->pluck('name')->values());

        if (supplierMasterSelect) {
            supplierMasterSelect.addEventListener('change', () => {
                const selected = supplierMasterSelect.selectedOptions[0];
                if (!selected || !selected.value) {
                    return;
                }

                document.getElementById('supplier_name').value = selected.dataset.name || '';
                document.getElementById('supplier_address').value = selected.dataset.address || '';
                document.getElementById('supplier_gstin').value = selected.dataset.gstin || '';
                document.getElementById('supplier_tds_section').value = selected.dataset.tdsSection || '';
                document.getElementById('tds_percent').value = selected.dataset.tdsPercent || 0;
                document.getElementById('e_invoice_applicable').value = selected.dataset.eInvoice || '0';
                document.getElementById('e_way_bill_applicable').value = selected.dataset.eWayBill || '0';
                document.getElementById('vendor_reconciliation_status').value = selected.dataset.vendorReconciliation || '';
                document.getElementById('auditor_export_note').value = selected.dataset.auditorExportNote || '';
                document.getElementById('dispatched_through').value = selected.dataset.dispatch || '';
                document.getElementById('destination').value = selected.dataset.destination || '';

                if (selected.dataset.terms) {
                    document.getElementById('terms').value = selected.dataset.terms;
                }

                calculatePo();
            });
        }

        function updatePoIndexes() {
            body.querySelectorAll('[data-po-item]').forEach((row, index) => {
                row.querySelector('.po-sr').textContent = index + 1;
                row.querySelectorAll('textarea, input').forEach((input) => {
                    input.name = input.name ? input.name.replace(/items\[\d+\]/, `items[${index}]`) : input.name;
                });
            });
        }

        function calculatePo() {
            let subtotal = 0;
            body.querySelectorAll('[data-po-item]').forEach((row) => {
                const qty = parseFloat(row.querySelector('.po-qty')?.value || 0);
                const rate = parseFloat(row.querySelector('.po-rate')?.value || 0);
                const amount = qty * rate;
                subtotal += amount;
                row.querySelector('.po-amount').value = amount.toFixed(2);
            });

            const tax = Array.from(document.querySelectorAll('.po-tax')).reduce((sum, input) => sum + parseFloat(input.value || 0), 0);
            const total = subtotal + tax;
            const tdsPercent = parseFloat(document.getElementById('tds_percent')?.value || 0);
            const tdsAmount = total * (tdsPercent / 100);
            document.getElementById('po-subtotal').value = subtotal.toFixed(2);
            document.getElementById('po-total').value = total.toFixed(2);
            document.getElementById('po-tds-amount').value = tdsAmount.toFixed(2);
            document.getElementById('po-net-payable').value = (total - tdsAmount).toFixed(2);
        }

        document.getElementById('add-po-item').addEventListener('click', () => {
            const index = body.querySelectorAll('[data-po-item]').length;
            const row = document.createElement('tr');
            row.setAttribute('data-po-item', '');
            row.innerHTML = `
                <td class="po-sr">${index + 1}</td>
                <td><textarea name="items[${index}][item_description]"></textarea></td>
                <td><input name="items[${index}][hsn_code]"></td>
                <td><input class="po-qty" name="items[${index}][quantity]" type="number" min="0" step="0.001" value="0"></td>
                <td>
                    <select name="items[${index}][unit]">
                        <option value="">Unit</option>
                        ${unitOptions.map((unit) => `<option value="${String(unit).replace(/"/g, '&quot;')}">${unit}</option>`).join('')}
                    </select>
                </td>
                <td><input class="po-rate" name="items[${index}][rate]" type="number" min="0" step="0.01" value="0"></td>
                <td><input class="po-amount" type="number" value="0.00" readonly></td>
                <td><button class="btn small secondary po-remove" type="button">-</button></td>
            `;
            body.appendChild(row);
        });

        document.addEventListener('input', (event) => {
            if (event.target.matches('.po-qty, .po-rate, .po-tax, .po-tds')) {
                calculatePo();
            }
        });

        document.addEventListener('click', (event) => {
            if (event.target.matches('.po-remove') && body.querySelectorAll('[data-po-item]').length > 1) {
                event.target.closest('[data-po-item]').remove();
                updatePoIndexes();
                calculatePo();
            }
        });

        calculatePo();
    </script>
@endsection
