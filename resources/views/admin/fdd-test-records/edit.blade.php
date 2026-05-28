@extends('admin.layouts.app')

@section('title', 'Edit FDD Test Record | Admin Panel')
@section('headerTitle', 'Edit FDD Test Record')
@section('headerSubtitle', 'Update road-wise material testing details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit FDD Test Record</h1>
            <p>Update the road section, test date, material, location, or table order.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.fdd-test-records.index') }}">Back to FDD Records</a>
    </div>

    <form class="card form-card" method="POST" action="{{ route('admin.fdd-test-records.update', $record) }}">
        @csrf
        @method('PUT')

        <section class="form-section">
            <h2 class="section-title">Record Details</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="fdd_road_section_id">Road / Section</label>
                    <select id="fdd_road_section_id" name="fdd_road_section_id" required>
                        <option value="">Select Road / Section</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('fdd_road_section_id', $record->fdd_road_section_id) === (string) $section->id)>
                                {{ $section->group_number }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('fdd_road_section_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="test_date">Date</label>
                    <input id="test_date" name="test_date" type="date" value="{{ old('test_date', $record->test_date?->toDateString()) }}">
                    @error('test_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="material">Material</label>
                    <input id="material" name="material" value="{{ old('material', $record->material) }}" required>
                    @error('material')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="sort_order">Row Order</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $record->sort_order) }}">
                    @error('sort_order')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field full">
                    <label for="location">Location</label>
                    <textarea id="location" name="location" required>{{ old('location', $record->location) }}</textarea>
                    @error('location')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Update Record</button>
            <a class="btn secondary" href="{{ route('admin.fdd-test-records.index') }}">Cancel</a>
        </div>
    </form>
@endsection
