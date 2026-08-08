@extends('admin.layouts.app')

@section('title', 'Labour Master | Admin Panel')
@section('headerTitle', 'Labour Master')
@section('headerSubtitle', 'Create and manage labour records')

@section('content')
    <div class="page-header">
        <div>
            <h1>Labour Master</h1>
            <p>Maintain labour names, mobile numbers, labour codes, and trades.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.labour-attendance.index') }}">View Attendance</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.labours.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Labour</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="contractor_id">Contractor</label>
                    <select id="contractor_id" name="contractor_id">
                        <option value="">Select Contractor</option>
                        @foreach ($contractors as $contractor)
                            <option value="{{ $contractor->id }}" @selected((string) old('contractor_id') === (string) $contractor->id)>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
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
                    <label for="labour_type">Labour Type</label>
                    <select id="labour_type" name="labour_type">
                        <option value="daily_wage" @selected(old('labour_type', 'daily_wage') === 'daily_wage')>Daily Wage</option>
                        <option value="permanent" @selected(old('labour_type') === 'permanent')>Permanent</option>
                    </select>
                </div>
                <div class="field">
                    <label for="work_category">Work Category</label>
                    <input id="work_category" name="work_category" type="text" value="{{ old('work_category') }}" placeholder="Mason, Helper, Excavation">
                </div>
                <div class="field">
                    <label for="daily_wage_rate">Daily Wage Rate</label>
                    <input id="daily_wage_rate" name="daily_wage_rate" type="number" min="0" step="0.01" value="{{ old('daily_wage_rate', 0) }}">
                </div>
                <div class="field">
                    <label for="overtime_rate">Overtime Rate</label>
                    <input id="overtime_rate" name="overtime_rate" type="number" min="0" step="0.01" value="{{ old('overtime_rate', 0) }}">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Add Labour</button>
            </div>
        </section>
    </form>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.labours.index') }}">
        <section class="form-section">
            <h2 class="section-title">Labour Filter</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="filter_contractor_id">Contractor</label>
                    <select id="filter_contractor_id" name="contractor_id">
                        <option value="">All Contractors</option>
                        @foreach ($contractors as $contractor)
                            <option value="{{ $contractor->id }}" @selected((string) $selectedContractorId === (string) $contractor->id)>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <div class="actions compact-actions">
                        <button class="btn" type="submit">Apply Filter</button>
                        <a class="btn secondary" href="{{ route('admin.labours.index') }}">Clear</a>
                    </div>
                </div>
            </div>
        </section>
    </form>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Contractor</th>
                    <th>Labour Name</th>
                    <th>Mobile</th>
                    <th>Code</th>
                    <th>Trade / Category</th>
                    <th>Wage</th>
                    <th>Status</th>
                    <th>Attendance</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($labours as $labour)
                    <tr>
                        <td>{{ $labour->id }}</td>
                        <td>{{ $labour->contractor?->name ?? '-' }}</td>
                        <td>{{ $labour->name }}</td>
                        <td>{{ $labour->mobile ?? '-' }}</td>
                        <td>{{ $labour->labour_code ?? '-' }}</td>
                        <td>
                            {{ $labour->trade ?? '-' }}
                            <div class="table-subtext">{{ ucfirst(str_replace('_', ' ', $labour->labour_type ?? 'daily_wage')) }} | {{ $labour->work_category ?? '-' }}</div>
                        </td>
                        <td>
                            {{ number_format((float) $labour->daily_wage_rate, 2) }}
                            <div class="table-subtext">OT {{ number_format((float) $labour->overtime_rate, 2) }}</div>
                        </td>
                        <td>
                            <span class="status-pill {{ $labour->is_active ? 'status-approved' : 'status-rejected' }}">
                                {{ $labour->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $labour->labour_attendances_count }}</td>
                        <td>{{ $labour->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn small secondary" href="{{ route('admin.labours.edit', $labour) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.labours.destroy', $labour) }}" onsubmit="return confirm('Delete this labour?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="11">No labours added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $labours->links('admin.pagination') }}
    </div>
@endsection
