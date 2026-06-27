@extends('admin.layouts.app')

@section('title', 'Material Stock | Admin Panel')
@section('headerTitle', 'Material Stock')
@section('headerSubtitle', 'Check available material by store or site')

@section('content')
    <div class="page-header">
        <div>
            <h1>Material Stock</h1>
            <p>View stock, add adjustment entries, and track low stock items.</p>
        </div>
    </div>

    @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="alert-error">{{ session('error') }}</div> @endif
    @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <section class="stats-grid">
        <div class="card stat-card"><span>Materials</span><strong>{{ $summary['materials'] }}</strong></div>
        <div class="card stat-card"><span>Stock Rows</span><strong>{{ $summary['stock_rows'] }}</strong></div>
        <div class="card stat-card"><span>Low Stock</span><strong>{{ $summary['low_stock'] }}</strong></div>
        <div class="card stat-card"><span>Sites</span><strong>{{ $sites->count() }}</strong></div>
    </section>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.material-stock.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filter</h2>
            <div class="form-grid three">
                <div class="field">
                    <label>Material</label>
                    <select name="material_id">
                        <option value="">All Materials</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}" @selected((int) $selectedMaterialId === (int) $material->id)>{{ $material->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Site / Store</label>
                    <select name="labour_site_id">
                        <option value="">All</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected((int) $selectedSiteId === (int) $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Show Stock</button>
                </div>
            </div>
        </section>
    </form>

    <form class="card form-card" method="POST" action="{{ route('admin.material-stock.adjust') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Stock Adjustment</h2>
            <div class="form-grid">
                <div class="field">
                    <label>Material</label>
                    <select name="material_id" required>
                        <option value="">Select material</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}">{{ $material->name }}{{ $material->unit ? ' ('.$material->unit.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Site / Store</label>
                    <select name="labour_site_id">
                        <option value="">Main Store</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Type</label>
                    <select name="type" required>
                        <option value="adjustment_in">Add Stock</option>
                        <option value="adjustment_out">Remove Stock</option>
                        <option value="return_in">Return In</option>
                    </select>
                </div>
                <div class="field">
                    <label>Quantity</label>
                    <input name="quantity" type="number" min="0.01" step="0.01" required>
                </div>
                <div class="field">
                    <label>Remarks</label>
                    <input name="remarks">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Update Stock</button>
                </div>
            </div>
        </section>
    </form>

    <div class="card table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Type</th>
                        <th>Unit</th>
                        <th>Site / Store</th>
                        <th>Available</th>
                        <th>Minimum</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stocks as $stock)
                        @php($low = (float) $stock->available_quantity <= (float) $stock->material->minimum_stock)
                        <tr>
                            <td>{{ $stock->material->name }}</td>
                            <td>{{ $stock->material->material_type ?? '-' }}</td>
                            <td>{{ $stock->material->unit ?? '-' }}</td>
                            <td>{{ $stock->site?->name ?? 'Main Store' }}</td>
                            <td>{{ number_format($stock->available_quantity, 2) }}</td>
                            <td>{{ number_format($stock->material->minimum_stock, 2) }}</td>
                            <td><span class="status-pill {{ $low ? 'status-open' : 'status-approved' }}">{{ $low ? 'Low Stock' : 'Available' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No stock rows found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination">{{ $stocks->links('admin.pagination') }}</div>
@endsection
