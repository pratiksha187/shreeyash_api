@extends('admin.layouts.app')

@section('title', 'Edit Labour | Admin Panel')
@section('headerTitle', 'Edit Labour')
@section('headerSubtitle', 'Update labour master details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Labour</h1>
            <p>Update labour name, mobile number, code, trade, and active status.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.labours.index') }}">Back to Labour Master</a>
    </div>

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card" method="POST" action="{{ route('admin.labours.update', $labour) }}">
        @csrf
        @method('PUT')

        <section class="form-section">
            <h2 class="section-title">Labour Details</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="contractor_id">Contractor</label>
                    <select id="contractor_id" name="contractor_id">
                        <option value="">Select Contractor</option>
                        @foreach ($contractors as $contractor)
                            <option value="{{ $contractor->id }}" @selected((string) old('contractor_id', $labour->contractor_id) === (string) $contractor->id)>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="name">Labour Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $labour->name) }}" required>
                </div>
                <div class="field">
                    <label for="mobile">Mobile</label>
                    <input id="mobile" name="mobile" type="text" value="{{ old('mobile', $labour->mobile) }}">
                </div>
                <div class="field">
                    <label for="labour_code">Labour Code</label>
                    <input id="labour_code" name="labour_code" type="text" value="{{ old('labour_code', $labour->labour_code) }}">
                </div>
                <div class="field">
                    <label for="trade">Trade</label>
                    <input id="trade" name="trade" type="text" value="{{ old('trade', $labour->trade) }}">
                </div>
                <div class="field">
                    <label for="labour_type">Labour Type</label>
                    <select id="labour_type" name="labour_type">
                        <option value="daily_wage" @selected(old('labour_type', $labour->labour_type ?? 'daily_wage') === 'daily_wage')>Daily Wage</option>
                        <option value="permanent" @selected(old('labour_type', $labour->labour_type) === 'permanent')>Permanent</option>
                    </select>
                </div>
                <div class="field">
                    <label for="work_category">Work Category</label>
                    <input id="work_category" name="work_category" type="text" value="{{ old('work_category', $labour->work_category) }}">
                </div>
                <div class="field">
                    <label for="daily_wage_rate">Daily Wage Rate</label>
                    <input id="daily_wage_rate" name="daily_wage_rate" type="number" min="0" step="0.01" value="{{ old('daily_wage_rate', $labour->daily_wage_rate ?? 0) }}">
                </div>
                <div class="field">
                    <label for="overtime_rate">Overtime Rate</label>
                    <input id="overtime_rate" name="overtime_rate" type="number" min="0" step="0.01" value="{{ old('overtime_rate', $labour->overtime_rate ?? 0) }}">
                </div>
                <div class="field">
                    <label for="is_active">Status</label>
                    <select id="is_active" name="is_active">
                        <option value="1" @selected((string) old('is_active', (int) $labour->is_active) === '1')>Active</option>
                        <option value="0" @selected((string) old('is_active', (int) $labour->is_active) === '0')>Inactive</option>
                    </select>
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Update Labour</button>
            <a class="btn secondary" href="{{ route('admin.labours.index') }}">Cancel</a>
        </div>
    </form>
@endsection
