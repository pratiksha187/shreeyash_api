@extends('admin.layouts.app')

@section('title', 'Labour Masters | Admin Panel')
@section('headerTitle', 'Labour Masters')
@section('headerSubtitle', 'Manage site, contractor, and labour master data')

@section('content')
    <div class="page-header">
        <div>
            <h1>Labour Masters</h1>
            <p>Use separate master pages to create, edit, and manage the mobile app lists.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.labour-attendance.index') }}">View Attendance</a>
    </div>

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

    <div class="sheet-summary-grid">
        <div class="card form-card">
            <section class="form-section">
                <h2 class="section-title">Site Master</h2>
                <p>Create sites, update addresses, and keep old sites inactive when needed.</p>
                <div class="actions">
                    <a class="btn" href="{{ route('admin.labour-sites.index') }}">Open Site Master</a>
                </div>
            </section>
        </div>

        <div class="card form-card">
            <section class="form-section">
                <h2 class="section-title">Contractor Master</h2>
                <p>Create contractors and maintain mobile numbers used by labour attendance.</p>
                <div class="actions">
                    <a class="btn" href="{{ route('admin.contractors.index') }}">Open Contractor Master</a>
                </div>
            </section>
        </div>
    </div>

    <div class="card form-card">
        <section class="form-section">
            <h2 class="section-title">Labour Master</h2>
            <p>Create labour records with mobile number, labour code, trade, and active status.</p>
            <div class="actions">
                <a class="btn" href="{{ route('admin.labours.index') }}">Open Labour Master</a>
            </div>
        </section>
    </div>
@endsection
