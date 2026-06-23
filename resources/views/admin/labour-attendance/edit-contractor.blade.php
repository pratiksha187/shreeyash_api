@extends('admin.layouts.app')

@section('title', 'Edit Contractor | Admin Panel')
@section('headerTitle', 'Edit Contractor')
@section('headerSubtitle', 'Update contractor master details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Contractor</h1>
            <p>Update contractor name, mobile number, and active status.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.contractors.index') }}">Back to Contractor Master</a>
    </div>

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card" method="POST" action="{{ route('admin.contractors.update', $contractor) }}">
        @csrf
        @method('PUT')

        <section class="form-section">
            <h2 class="section-title">Contractor Details</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="name">Contractor Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $contractor->name) }}" required>
                </div>
                <div class="field">
                    <label for="mobile">Mobile</label>
                    <input id="mobile" name="mobile" type="text" value="{{ old('mobile', $contractor->mobile) }}">
                </div>
                <div class="field">
                    <label for="is_active">Status</label>
                    <select id="is_active" name="is_active">
                        <option value="1" @selected((string) old('is_active', (int) $contractor->is_active) === '1')>Active</option>
                        <option value="0" @selected((string) old('is_active', (int) $contractor->is_active) === '0')>Inactive</option>
                    </select>
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Update Contractor</button>
            <a class="btn secondary" href="{{ route('admin.contractors.index') }}">Cancel</a>
        </div>
    </form>
@endsection
