@extends('admin.layouts.app')

@section('title', 'Product Purchase | Admin Panel')
@section('headerTitle', 'Product Purchase')
@section('headerSubtitle', 'Track purchased products, supplier bills, and monthly totals')

@section('content')
    <style>
        .purchase-workspace {
            display: grid;
            gap: 22px;
        }

        .purchase-panel {
            padding: 18px;
            overflow: hidden;
        }

        .purchase-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .purchase-panel-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            line-height: 1.25;
        }

        .purchase-panel-head p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .purchase-table-scroll {
            overflow-x: hidden;
            width: 100%;
        }

        .purchase-table {
            width: 100%;
            min-width: 0;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .purchase-table th,
        .purchase-table td {
            padding: 6px 7px;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            font-size: 12px;
            text-align: center;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        .purchase-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            text-transform: none;
        }

        .purchase-table th:nth-child(1),
        .purchase-table td:nth-child(1) {
            width: 9%;
        }

        .purchase-table th:nth-child(2),
        .purchase-table td:nth-child(2) {
            width: 9%;
        }

        .purchase-table th:nth-child(3),
        .purchase-table td:nth-child(3) {
            width: 12%;
        }

        .purchase-table th:nth-child(4),
        .purchase-table td:nth-child(4) {
            width: 7%;
        }

        .purchase-table th:nth-child(5),
        .purchase-table td:nth-child(5) {
            width: 11%;
        }

        .purchase-table th:nth-child(6),
        .purchase-table td:nth-child(6) {
            width: 8%;
        }

        .purchase-table th:nth-child(7),
        .purchase-table td:nth-child(7) {
            width: 6%;
        }

        .purchase-table th:nth-child(8),
        .purchase-table td:nth-child(8) {
            width: 8%;
        }

        .purchase-table th:nth-child(9),
        .purchase-table td:nth-child(9) {
            width: 7%;
        }

        .purchase-table th:nth-child(10),
        .purchase-table td:nth-child(10) {
            width: 8%;
        }

        .purchase-table th:nth-child(11),
        .purchase-table td:nth-child(11) {
            width: 8%;
        }

        .purchase-table th:nth-child(12),
        .purchase-table td:nth-child(12) {
            width: 7%;
        }

        .purchase-table input,
        .purchase-table select,
        .purchase-table textarea {
            width: 100%;
            min-width: 0;
            min-height: 30px;
            padding: 4px 5px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #fff;
            font-size: 12px;
            text-align: center;
        }

        .purchase-table textarea {
            min-width: 0;
            min-height: 34px;
            resize: vertical;
            text-align: left;
        }

        .purchase-table input:focus,
        .purchase-table select:focus,
        .purchase-table textarea:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
        }

        .purchase-table input[readonly] {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 800;
        }

        .purchase-table .sheet-highlight {
            background: #fff200;
        }

        .purchase-table .purchase-text-input {
            min-width: 0;
        }

        .purchase-table .purchase-date-input {
            min-width: 0;
        }

        .purchase-material-select,
        .purchase-supplier-select {
            text-align: left;
        }

        .purchase-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            min-width: 0;
        }

        .purchase-actions form {
            margin: 0;
        }

        .purchase-actions .btn.small {
            width: 100%;
            min-height: 30px;
            padding: 5px 7px;
            font-size: 12px;
        }

        .purchase-filter-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            padding: 0 2px;
            color: #0f172a;
            font-weight: 800;
        }

        .purchase-filter-toggle input {
            width: 18px;
            height: 18px;
            accent-color: #2563eb;
        }

        .purchase-entry-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 14px 12px;
            align-items: end;
        }

        .purchase-entry-grid .field {
            min-width: 0;
        }

        .purchase-entry-grid .field.wide {
            grid-column: span 2;
        }

        .purchase-entry-grid input,
        .purchase-entry-grid select,
        .purchase-entry-grid textarea {
            width: 100%;
            min-width: 0;
        }

        .purchase-entry-grid textarea {
            min-height: 52px;
            resize: vertical;
        }

        .purchase-entry-grid .amount-preview {
            min-height: 52px;
            display: flex;
            align-items: center;
            padding: 0 14px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #f8fafc;
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
        }

        @media (max-width: 640px) {
            .purchase-panel-head {
                flex-direction: column;
            }
        }

        @media (max-width: 1280px) {
            .purchase-entry-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .purchase-entry-grid {
                grid-template-columns: 1fr;
            }

            .purchase-entry-grid .field.wide {
                grid-column: span 1;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>{{ $showAll ? 'All Product Purchase' : $monthLabel.' Product Purchase' }}</h1>
            <p>Enter site-wise purchase bills with PCS, weight, rate, and auto amount.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.product-purchases.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filter</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="month">Month</label>
                    <input id="month" name="month" type="month" value="{{ old('month', $selectedMonth) }}" @disabled($showAll)>
                    @error('month')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="field">
                    <label for="search">Search</label>
                    <input id="search" name="search" value="{{ old('search', $search) }}" placeholder="Product, supplier, invoice">
                    @error('search')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="field">
                    <label for="stock_labour_site_id">Site</label>
                    <select id="stock_labour_site_id" name="stock_labour_site_id">
                        <option value="">All sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected((string) $selectedSiteId === (string) $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <label class="purchase-filter-toggle" for="show_all">
                        <input id="show_all" name="show_all" type="checkbox" value="1" @checked($showAll)>
                        <span>Show all data</span>
                    </label>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Show Purchases</button>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total PCS</span>
            <strong>{{ number_format($summary['pcs'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Total Weight KG</span>
            <strong>{{ number_format($summary['weight_kg'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Total Amount</span>
            <strong>{{ number_format($summary['amount'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Products</span>
            <strong>{{ $summary['products'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Suppliers</span>
            <strong>{{ $summary['suppliers'] }}</strong>
        </div>
    </section>

    <div class="purchase-workspace">
        <section class="card purchase-panel">
            <div class="purchase-panel-head">
                <div>
                    <h2>Add Purchase</h2>
                    <p>Amount uses Weight KG x Rate. If weight is blank, it uses PCS x Rate.</p>
                </div>
            </div>

            <form id="create-purchase-form" class="purchase-entry-grid" method="POST" action="{{ route('admin.product-purchases.store') }}" data-purchase-row>
                @csrf
                <div class="field">
                    <label>Stock Site</label>
                    <select name="stock_labour_site_id">
                        <option value="">Main Store</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Date</label>
                    <input class="sheet-highlight" name="purchase_date" type="date" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="field wide">
                    <label>Party Name</label>
                    <select class="js-supplier-select" name="supplier_id" required>
                        <option value="">Select party</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" data-name="{{ $supplier->name }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <input name="supplier_name" type="hidden" value="{{ old('supplier_name') }}" required>
                </div>
                <div class="field">
                    <label>Challan No</label>
                    <input name="invoice_no">
                </div>
                <div class="field wide">
                    <label>Item Name</label>
                    <select class="js-material-select" name="material_id" required>
                        <option value="">Select material</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}" data-name="{{ $material->name }}" data-unit="{{ $material->unit }}" @selected((string) old('material_id') === (string) $material->id)>{{ $material->name }}{{ $material->unit ? ' - '.$material->unit : '' }}</option>
                        @endforeach
                    </select>
                    <input name="product_name" type="hidden" value="{{ old('product_name') }}" required>
                    <input name="unit" type="hidden" value="{{ old('unit') }}">
                </div>
                <div class="field">
                    <label>Size</label>
                    <input class="sheet-highlight" name="size">
                </div>
                <div class="field">
                    <label>PCS</label>
                    <input class="js-pcs" name="pcs" type="number" min="0" step="0.01" value="0">
                </div>
                <div class="field">
                    <label>Weight In KG</label>
                    <input class="js-weight" name="weight_kg" type="number" min="0" step="0.01" value="0">
                </div>
                <div class="field">
                    <label>Rate</label>
                    <input class="js-rate" name="rate" type="number" min="0" step="0.01" value="0" required>
                </div>
                <div class="field">
                    <label>Amt</label>
                    <div class="amount-preview js-total-preview">0.00</div>
                    <input class="js-total" type="hidden" value="0.00" readonly>
                    <input name="quantity" type="hidden" value="0">
                    <input name="tax_amount" type="hidden" value="0">
                    <input name="transport_amount" type="hidden" value="0">
                </div>
                <div class="field wide">
                    <label>Remark</label>
                    <textarea name="remarks"></textarea>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Save Purchase</button>
                </div>
            </form>
        </section>

        <section class="card purchase-panel">
            <div class="purchase-panel-head">
                <div>
                    <h2>Purchase List</h2>
                    <p>{{ $purchases->count() }} entries found {{ $showAll ? 'in all records' : 'for '.$monthLabel }}.</p>
                </div>
            </div>

            <div class="purchase-table-scroll">
                <table class="purchase-table">
                    <thead>
                        <tr>
                            <th>Stock Site</th>
                            <th>Date</th>
                            <th>Party Name</th>
                            <th>Challan No</th>
                            <th>Item Name</th>
                            <th>Size</th>
                            <th>PCS</th>
                            <th>Weight In KG</th>
                            <th>Rate</th>
                            <th>Amt</th>
                            <th>Remark</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            @php
                                $formId = 'purchase-form-'.$purchase->id;
                                $deleteFormId = 'purchase-delete-form-'.$purchase->id;
                            @endphp
                            <tr data-purchase-row>
                                <td>
                                    <select class="purchase-text-input" form="{{ $formId }}" name="stock_labour_site_id">
                                        <option value="">Main Store</option>
                                        @foreach ($sites as $site)
                                            <option value="{{ $site->id }}" @selected((int) $purchase->stock_labour_site_id === (int) $site->id)>{{ $site->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="purchase-date-input sheet-highlight" form="{{ $formId }}" name="purchase_date" type="date" value="{{ $purchase->purchase_date->toDateString() }}" required></td>
                                <td>
                                    <select class="purchase-supplier-select js-supplier-select" form="{{ $formId }}" name="supplier_id" required>
                                        @php
                                            $matchedSupplier = $suppliers->firstWhere('name', $purchase->supplier_name);
                                        @endphp
                                        @if (! $matchedSupplier)
                                            <option value="" selected>{{ $purchase->supplier_name ?: 'Select party' }}</option>
                                        @else
                                            <option value="">Select party</option>
                                        @endif
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" data-name="{{ $supplier->name }}" @selected($matchedSupplier && (int) $matchedSupplier->id === (int) $supplier->id)>{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                    <input form="{{ $formId }}" name="supplier_name" type="hidden" value="{{ $purchase->supplier_name }}" required>
                                </td>
                                <td><input form="{{ $formId }}" name="invoice_no" value="{{ $purchase->invoice_no }}"></td>
                                <td>
                                    <select class="purchase-material-select js-material-select" form="{{ $formId }}" name="material_id" required>
                                        @if (! $purchase->material_id)
                                            <option value="" selected>{{ $purchase->product_name ?: 'Select material' }}</option>
                                        @else
                                            <option value="">Select material</option>
                                        @endif
                                        @foreach ($materials as $material)
                                            <option
                                                value="{{ $material->id }}"
                                                data-name="{{ $material->name }}"
                                                data-unit="{{ $material->unit }}"
                                                @selected((int) $purchase->material_id === (int) $material->id)
                                            >{{ $material->name }}{{ $material->unit ? ' - '.$material->unit : '' }}</option>
                                        @endforeach
                                    </select>
                                    <input form="{{ $formId }}" name="product_name" type="hidden" value="{{ $purchase->product_name }}" required>
                                </td>
                                <td><input class="sheet-highlight" form="{{ $formId }}" name="size" value="{{ $purchase->size }}"></td>
                                <td><input class="js-pcs" form="{{ $formId }}" name="pcs" type="number" min="0" step="0.01" value="{{ number_format($purchase->pcs, 2, '.', '') }}"></td>
                                <td><input class="js-weight" form="{{ $formId }}" name="weight_kg" type="number" min="0" step="0.01" value="{{ number_format($purchase->weight_kg, 2, '.', '') }}"></td>
                                <td><input class="js-rate" form="{{ $formId }}" name="rate" type="number" min="0" step="0.01" value="{{ number_format($purchase->rate, 2, '.', '') }}" required></td>
                                <td>
                                    <input class="js-total" type="number" value="{{ number_format($purchase->total_amount, 2, '.', '') }}" readonly>
                                    <input form="{{ $formId }}" name="unit" type="hidden" value="{{ $purchase->unit }}">
                                    <input form="{{ $formId }}" name="quantity" type="hidden" value="{{ number_format($purchase->quantity, 2, '.', '') }}">
                                    <input form="{{ $formId }}" name="tax_amount" type="hidden" value="{{ number_format($purchase->tax_amount, 2, '.', '') }}">
                                    <input form="{{ $formId }}" name="transport_amount" type="hidden" value="{{ number_format($purchase->transport_amount, 2, '.', '') }}">
                                </td>
                                <td><textarea form="{{ $formId }}" name="remarks">{{ $purchase->remarks }}</textarea></td>
                                <td>
                                    <div class="purchase-actions">
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.product-purchases.update', $purchase) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <form id="{{ $deleteFormId }}" method="POST" action="{{ route('admin.product-purchases.destroy', $purchase) }}" onsubmit="return confirm('Delete this product purchase?')">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button class="btn small" form="{{ $formId }}" type="submit">Update</button>
                                        <button class="btn danger small" form="{{ $deleteFormId }}" type="submit">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12">No product purchase entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        const showAllFilter = document.getElementById('show_all');
        const monthFilter = document.getElementById('month');

        if (showAllFilter && monthFilter) {
            showAllFilter.addEventListener('change', () => {
                monthFilter.disabled = showAllFilter.checked;
            });
        }

        document.querySelectorAll('[data-purchase-row]').forEach((row) => {
            const applySupplier = () => {
                const supplierSelect = row.querySelector('.js-supplier-select');
                const selected = supplierSelect?.selectedOptions[0];
                const supplierName = row.querySelector('input[name="supplier_name"]');

                if (!supplierSelect || !selected || !selected.value || !supplierName) {
                    return;
                }

                supplierName.value = selected.dataset.name || selected.textContent.trim();
            };

            const applyMaterial = () => {
                const materialSelect = row.querySelector('.js-material-select');
                const selected = materialSelect?.selectedOptions[0];
                const productName = row.querySelector('input[name="product_name"]');
                const unit = row.querySelector('input[name="unit"]');

                if (!materialSelect || !selected || !selected.value) {
                    return;
                }

                if (productName) {
                    productName.value = selected.dataset.name || selected.textContent.trim();
                }

                if (unit) {
                    unit.dataset.masterUnit = selected.dataset.unit || '';
                    unit.value = selected.dataset.unit || unit.value;
                }
            };

            const recalculate = () => {
                const pcs = parseFloat(row.querySelector('.js-pcs')?.value || 0);
                const weight = parseFloat(row.querySelector('.js-weight')?.value || 0);
                const rate = parseFloat(row.querySelector('.js-rate')?.value || 0);
                const total = row.querySelector('.js-total');
                const quantity = row.querySelector('input[name="quantity"]');
                const unit = row.querySelector('input[name="unit"]');
                const billingQuantity = weight > 0 ? weight : pcs;

                if (total) {
                    total.value = (billingQuantity * rate).toFixed(2);
                }

                const totalPreview = row.querySelector('.js-total-preview');
                if (totalPreview) {
                    totalPreview.textContent = (billingQuantity * rate).toFixed(2);
                }

                if (quantity) {
                    quantity.value = billingQuantity.toFixed(2);
                }

                if (unit) {
                    unit.value = unit.dataset.masterUnit || (weight > 0 ? 'Kg' : 'Nos');
                }
            };

            row.querySelectorAll('input, select').forEach((input) => {
                input.addEventListener('input', () => {
                    applySupplier();
                    applyMaterial();
                    recalculate();
                });
                input.addEventListener('change', () => {
                    applySupplier();
                    applyMaterial();
                    recalculate();
                });
            });

            applySupplier();
            applyMaterial();
            recalculate();
        });
    </script>
@endsection
