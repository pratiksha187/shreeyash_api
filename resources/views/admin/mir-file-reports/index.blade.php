@extends('admin.layouts.app')

@section('title', 'MIR File Report | Admin Panel')
@section('headerTitle', 'MIR File Report')
@section('headerSubtitle', 'Material inward report register')

@php
    $formatQuantity = fn ($quantity) => rtrim(rtrim(number_format((float) $quantity, 2), '0'), '.');
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1>MIR File Report</h1>
            <p>Add and review material inward report entries with quantity, unit, and location.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.mir-file-reports.export', request()->query()) }}">Export CSV</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.mir-file-reports.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add MIR Entry</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="report_date">Date</label>
                    <input id="report_date" name="report_date" type="date" value="{{ old('report_date') }}">
                    @error('report_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="material">Material</label>
                    <input id="material" name="material" list="material-options" value="{{ old('material') }}" placeholder="RCC Hume Pipe 900 Mtr NP2" required>
                    <datalist id="material-options">
                        @foreach ($materials as $material)
                            <option value="{{ $material }}">
                        @endforeach
                    </datalist>
                    @error('material')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="quantity">QTY</label>
                    <input id="quantity" name="quantity" type="number" min="0" step="0.01" value="{{ old('quantity') }}" placeholder="10" required>
                    @error('quantity')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="unit">Unit</label>
                    <input id="unit" name="unit" list="unit-options" value="{{ old('unit') }}" placeholder="Nos" required>
                    <datalist id="unit-options">
                        @foreach ($units as $unit)
                            <option value="{{ $unit }}">
                        @endforeach
                    </datalist>
                    @error('unit')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="location">Location</label>
                    <input id="location" name="location" list="location-options" value="{{ old('location') }}" placeholder="Cluster 4" required>
                    <datalist id="location-options">
                        @foreach ($locations as $location)
                            <option value="{{ $location }}">
                        @endforeach
                    </datalist>
                    @error('location')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="sort_order">Row Order</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order') }}" placeholder="Auto">
                    @error('sort_order')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Add Record</button>
                </div>
            </div>
        </section>
    </form>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.mir-file-reports.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filters</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="from_date">From Date</label>
                    <input id="from_date" name="from_date" type="date" value="{{ old('from_date', $filters['from_date'] ?? '') }}">
                    @error('from_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="to_date">To Date</label>
                    <input id="to_date" name="to_date" type="date" value="{{ old('to_date', $filters['to_date'] ?? '') }}">
                    @error('to_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="filter_material">Material</label>
                    <select id="filter_material" name="material">
                        <option value="">All Materials</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material }}" @selected(($filters['material'] ?? '') === $material)>{{ $material }}</option>
                        @endforeach
                    </select>
                    @error('material')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="filter_unit">Unit</label>
                    <select id="filter_unit" name="unit">
                        <option value="">All Units</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit }}" @selected(($filters['unit'] ?? '') === $unit)>{{ $unit }}</option>
                        @endforeach
                    </select>
                    @error('unit')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="filter_location">Location</label>
                    <select id="filter_location" name="location">
                        <option value="">All Locations</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>{{ $location }}</option>
                        @endforeach
                    </select>
                    @error('location')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Apply Filter</button>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <a class="btn secondary" href="{{ route('admin.mir-file-reports.index') }}">Clear</a>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total Records</span>
            <strong>{{ $summary['total_records'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Total QTY</span>
            <strong>{{ $formatQuantity($summary['total_quantity']) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Materials</span>
            <strong>{{ $summary['materials'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Latest Date</span>
            <strong>{{ $summary['latest_date'] ? $summary['latest_date']->format('d M') : '-' }}</strong>
        </div>
    </section>

    <div class="card table-wrap">
        <div class="sheet-report-title">MIR File Report</div>
        <table class="sheet-table">
            <thead>
                <tr>
                    <th>Sr. No.</th>
                    <th>Date</th>
                    <th>Material</th>
                    <th>QTY</th>
                    <th>Unit</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td class="sheet-center">{{ $loop->iteration }}</td>
                        <td class="sheet-center">{{ $report->report_date?->format('d-m-Y') ?? '-' }}</td>
                        <td class="sheet-text">{{ $report->material }}</td>
                        <td class="sheet-center">{{ $formatQuantity($report->quantity) }}</td>
                        <td class="sheet-center">{{ $report->unit }}</td>
                        <td class="sheet-center">{{ $report->location }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn secondary small" href="{{ route('admin.mir-file-reports.edit', $report) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.mir-file-reports.destroy', $report) }}" onsubmit="return confirm('Delete this MIR record?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No MIR file report records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
