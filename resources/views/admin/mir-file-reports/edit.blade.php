@extends('admin.layouts.app')

@section('title', 'Edit MIR File Report | Admin Panel')
@section('headerTitle', 'Edit MIR File Report')
@section('headerSubtitle', 'Update material inward report details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit MIR File Report</h1>
            <p>Update date, material, quantity, unit, location, or row order.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.mir-file-reports.index') }}">Back to MIR File Report</a>
    </div>

    <form class="card form-card" method="POST" action="{{ route('admin.mir-file-reports.update', $report) }}">
        @csrf
        @method('PUT')

        <section class="form-section">
            <h2 class="section-title">Record Details</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="report_date">Date</label>
                    <input id="report_date" name="report_date" type="date" value="{{ old('report_date', $report->report_date?->toDateString()) }}">
                    @error('report_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="material">Material</label>
                    <input id="material" name="material" list="material-options" value="{{ old('material', $report->material) }}" required>
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
                    <input id="quantity" name="quantity" type="number" min="0" step="0.01" value="{{ old('quantity', $report->quantity) }}" required>
                    @error('quantity')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="unit">Unit</label>
                    <input id="unit" name="unit" list="unit-options" value="{{ old('unit', $report->unit) }}" required>
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
                    <input id="location" name="location" list="location-options" value="{{ old('location', $report->location) }}" required>
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
                    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $report->sort_order) }}">
                    @error('sort_order')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Update Record</button>
            <a class="btn secondary" href="{{ route('admin.mir-file-reports.index') }}">Cancel</a>
        </div>
    </form>
@endsection
