@extends('admin.layouts.app')

@section('title', 'Edit Site | Admin Panel')
@section('headerTitle', 'Edit Site')
@section('headerSubtitle', 'Update site master details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Site</h1>
            <p>Update the site name, address, and active status.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.labour-sites.index') }}">Back to Site Master</a>
    </div>

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card" method="POST" action="{{ route('admin.labour-sites.update', $site) }}">
        @csrf
        @method('PUT')

        <section class="form-section">
            <h2 class="section-title">Site Details</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="name">Site Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $site->name) }}" required>
                </div>
                <div class="field">
                    <label for="address">Address</label>
                    <input id="address" name="address" type="text" value="{{ old('address', $site->address) }}">
                </div>
                <div class="field">
                    <label for="is_active">Status</label>
                    <select id="is_active" name="is_active">
                        <option value="1" @selected((string) old('is_active', (int) $site->is_active) === '1')>Active</option>
                        <option value="0" @selected((string) old('is_active', (int) $site->is_active) === '0')>Inactive</option>
                    </select>
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Update Site</button>
            <a class="btn secondary" href="{{ route('admin.labour-sites.index') }}">Cancel</a>
        </div>
    </form>
@endsection
