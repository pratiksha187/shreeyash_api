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
            overflow-x: auto;
        }

        .purchase-table {
            width: 100%;
            min-width: 1220px;
            border-collapse: collapse;
        }

        .purchase-table th,
        .purchase-table td {
            padding: 9px 10px;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            font-size: 13px;
            text-align: center;
            vertical-align: top;
        }

        .purchase-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 12px;
            font-weight: 900;
            text-transform: none;
        }

        .purchase-table input,
        .purchase-table select,
        .purchase-table textarea {
            width: 100%;
            min-width: 92px;
            min-height: 32px;
            padding: 5px 7px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            text-align: center;
        }

        .purchase-table textarea {
            min-width: 150px;
            min-height: 36px;
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
            min-width: 140px;
        }

        .purchase-table .purchase-date-input {
            min-width: 136px;
        }

        .purchase-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            min-width: 150px;
        }

        .purchase-actions form {
            margin: 0;
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

        @media (max-width: 640px) {
            .purchase-panel-head {
                flex-direction: column;
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
                    <p>Amount uses Weight KG × Rate. If weight is blank, it uses PCS × Rate.</p>
                </div>
            </div>

            <form id="create-purchase-form" method="POST" action="{{ route('admin.product-purchases.store') }}">
                @csrf
            </form>

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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-purchase-row>
                            <td>
                                <select class="purchase-text-input" form="create-purchase-form" name="stock_labour_site_id">
                                    <option value="">Main Store</option>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input class="purchase-date-input sheet-highlight" form="create-purchase-form" name="purchase_date" type="date" value="{{ now()->toDateString() }}" required></td>
                            <td><input class="purchase-text-input" form="create-purchase-form" name="supplier_name"></td>
                            <td><input form="create-purchase-form" name="invoice_no"></td>
                            <td><input class="purchase-text-input" form="create-purchase-form" name="product_name" required></td>
                            <td><input class="sheet-highlight" form="create-purchase-form" name="size"></td>
                            <td><input class="js-pcs" form="create-purchase-form" name="pcs" type="number" min="0" step="0.01" value="0"></td>
                            <td><input class="js-weight" form="create-purchase-form" name="weight_kg" type="number" min="0" step="0.01" value="0"></td>
                            <td><input class="js-rate" form="create-purchase-form" name="rate" type="number" min="0" step="0.01" value="0" required></td>
                            <td>
                                <input class="js-total" type="number" value="0.00" readonly>
                                <input form="create-purchase-form" name="material_id" type="hidden">
                                <input form="create-purchase-form" name="unit" type="hidden">
                                <input form="create-purchase-form" name="quantity" type="hidden" value="0">
                                <input form="create-purchase-form" name="tax_amount" type="hidden" value="0">
                                <input form="create-purchase-form" name="transport_amount" type="hidden" value="0">
                            </td>
                            <td><textarea form="create-purchase-form" name="remarks"></textarea></td>
                            <td><button class="btn small" form="create-purchase-form" type="submit">Save</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
                                <td><input class="purchase-text-input" form="{{ $formId }}" name="supplier_name" value="{{ $purchase->supplier_name }}"></td>
                                <td><input form="{{ $formId }}" name="invoice_no" value="{{ $purchase->invoice_no }}"></td>
                                <td><input class="purchase-text-input" form="{{ $formId }}" name="product_name" value="{{ $purchase->product_name }}" required></td>
                                <td><input class="sheet-highlight" form="{{ $formId }}" name="size" value="{{ $purchase->size }}"></td>
                                <td><input class="js-pcs" form="{{ $formId }}" name="pcs" type="number" min="0" step="0.01" value="{{ number_format($purchase->pcs, 2, '.', '') }}"></td>
                                <td><input class="js-weight" form="{{ $formId }}" name="weight_kg" type="number" min="0" step="0.01" value="{{ number_format($purchase->weight_kg, 2, '.', '') }}"></td>
                                <td><input class="js-rate" form="{{ $formId }}" name="rate" type="number" min="0" step="0.01" value="{{ number_format($purchase->rate, 2, '.', '') }}" required></td>
                                <td>
                                    <input class="js-total" type="number" value="{{ number_format($purchase->total_amount, 2, '.', '') }}" readonly>
                                    <input form="{{ $formId }}" name="material_id" type="hidden" value="{{ $purchase->material_id }}">
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

                if (quantity) {
                    quantity.value = billingQuantity.toFixed(2);
                }

                if (unit) {
                    unit.value = weight > 0 ? 'Kg' : 'Nos';
                }
            };

            row.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', recalculate);
                input.addEventListener('change', recalculate);
            });

            recalculate();
        });
    </script>
@endsection
