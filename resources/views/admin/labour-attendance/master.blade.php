@extends('admin.layouts.app')

@section('title', 'Labour Master | Admin Panel')
@section('headerTitle', 'Labour Master')
@section('headerSubtitle', 'Manage site, contractor, and labour master data')

@section('content')
    <div class="page-header">
        <div>
            <h1>Labour Master</h1>
            <p>Manage the site, contractor, and labour lists used by the mobile app.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.labour-attendance.index') }}">View Attendance</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="sheet-summary-grid">
        <form class="card form-card report-filter" method="POST" action="{{ route('admin.labour-sites.store') }}">
            @csrf
            <section class="form-section">
                <h2 class="section-title">Add Site</h2>
                <div class="form-grid">
                    <div class="field">
                        <label for="site_name">Site Name</label>
                        <input id="site_name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label for="site_address">Address</label>
                        <input id="site_address" name="address" type="text" value="{{ old('address') }}">
                    </div>
                </div>
                <div class="actions">
                    <button class="btn" type="submit">Add Site</button>
                </div>
            </section>
        </form>

        <form class="card form-card report-filter" method="POST" action="{{ route('admin.contractors.store') }}">
            @csrf
            <section class="form-section">
                <h2 class="section-title">Add Contractor</h2>
                <div class="form-grid">
                    <div class="field">
                        <label for="contractor_name">Contractor Name</label>
                        <input id="contractor_name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label for="contractor_mobile">Mobile</label>
                        <input id="contractor_mobile" name="mobile" type="text" value="{{ old('mobile') }}">
                    </div>
                </div>
                <div class="actions">
                    <button class="btn" type="submit">Add Contractor</button>
                </div>
            </section>
        </form>
    </div>

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.labours.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Labour</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="labour_name">Labour Name</label>
                    <input id="labour_name" name="name" type="text" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="labour_mobile">Mobile</label>
                    <input id="labour_mobile" name="mobile" type="text" value="{{ old('mobile') }}">
                </div>
                <div class="field">
                    <label for="labour_code">Labour Code</label>
                    <input id="labour_code" name="labour_code" type="text" value="{{ old('labour_code') }}">
                </div>
                <div class="field">
                    <label for="trade">Trade</label>
                    <input id="trade" name="trade" type="text" value="{{ old('trade') }}">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Add Labour</button>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Sites</span>
            <strong>{{ $sites->count() }}</strong>
        </div>
        <div class="card stat-card">
            <span>Contractors</span>
            <strong>{{ $contractors->count() }}</strong>
        </div>
        <div class="card stat-card">
            <span>Labours</span>
            <strong>{{ $labours->count() }}</strong>
        </div>
        <div class="card stat-card">
            <span>Mobile Lists</span>
            <strong>Live</strong>
        </div>
    </section>
@endsection
