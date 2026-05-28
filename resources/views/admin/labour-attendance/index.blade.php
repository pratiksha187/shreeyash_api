@extends('admin.layouts.app')

@section('title', 'Labour Attendance | Admin Panel')
@section('headerTitle', 'Labour Attendance')
@section('headerSubtitle', 'Site, contractor, labour attendance approval')

@section('content')
    <div class="page-header">
        <div>
            <h1>Labour Attendance</h1>
            <p>Add sites, contractors, labours, and approve attendance submitted by engineers.</p>
        </div>
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
                        <label for="contractor_site">Site</label>
                        <select id="contractor_site" name="labour_site_id" required>
                            <option value="">Select Site</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" @selected(old('labour_site_id') == $site->id)>
                                    {{ $site->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
                    <label for="labour_contractor">Contractor</label>
                    <select id="labour_contractor" name="contractor_id" required>
                        <option value="">Select Contractor</option>
                        @foreach ($contractors as $contractor)
                            <option value="{{ $contractor->id }}" @selected(old('contractor_id') == $contractor->id)>
                                {{ $contractor->name }} - {{ $contractor->site?->name ?? '-' }}
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
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Add Labour</button>
                </div>
            </div>
        </section>
    </form>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.labour-attendance.index') }}">
        <section class="form-section">
            <h2 class="section-title">Attendance Filters</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="from_date">From Date</label>
                    <input id="from_date" name="from_date" type="date" value="{{ old('from_date', $fromDate) }}">
                </div>
                <div class="field">
                    <label for="to_date">To Date</label>
                    <input id="to_date" name="to_date" type="date" value="{{ old('to_date', $toDate) }}">
                </div>
                <div class="field">
                    <label for="filter_site">Site</label>
                    <select id="filter_site" name="labour_site_id">
                        <option value="">All Sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected((string) $selectedSiteId === (string) $site->id)>
                                {{ $site->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filter_contractor">Contractor</label>
                    <select id="filter_contractor" name="contractor_id">
                        <option value="">All Contractors</option>
                        @foreach ($contractors as $contractor)
                            <option value="{{ $contractor->id }}" @selected((string) $selectedContractorId === (string) $contractor->id)>
                                {{ $contractor->name }} - {{ $contractor->site?->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filter_labour">Labour</label>
                    <select id="filter_labour" name="labour_id">
                        <option value="">All Labours</option>
                        @foreach ($labours as $labour)
                            <option value="{{ $labour->id }}" @selected((string) $selectedLabourId === (string) $labour->id)>
                                {{ $labour->name }} - {{ $labour->contractor?->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="filter_engineer">Engineer</label>
                    <select id="filter_engineer" name="engineer_user_id">
                        <option value="">All Engineers</option>
                        @foreach ($engineers as $engineer)
                            <option value="{{ $engineer->id }}" @selected((string) $selectedEngineerId === (string) $engineer->id)>
                                {{ $engineer->name }}{{ $engineer->mobile ? ' - ' . $engineer->mobile : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="approval_status">Approval Status</label>
                    <select id="approval_status" name="approval_status">
                        <option value="">All Status</option>
                        @foreach ($approvalStatuses as $approvalStatus)
                            <option value="{{ $approvalStatus }}" @selected($selectedApprovalStatus === $approvalStatus)>
                                {{ ucfirst($approvalStatus) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Apply Filter</button>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total Entries</span>
            <strong>{{ $summary['total'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Pending</span>
            <strong>{{ $summary['pending'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Approved</span>
            <strong>{{ $summary['approved'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Rejected</span>
            <strong>{{ $summary['rejected'] }}</strong>
        </div>
    </section>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Site</th>
                    <th>Contractor</th>
                    <th>Labour</th>
                    <th>Photo</th>
                    <th>Attendance</th>
                    <th>Engineer</th>
                    <th>Approval</th>
                    <th>Admin Update</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->attendance_date?->format('d M Y') }}</td>
                        <td class="text-wrap">{{ $attendance->site?->name ?? '-' }}</td>
                        <td>
                            {{ $attendance->contractor?->name ?? '-' }}
                            @if ($attendance->contractor?->mobile)
                                <div class="table-subtext">{{ $attendance->contractor->mobile }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $attendance->labour?->name ?? '-' }}
                            <div class="table-subtext">
                                {{ $attendance->labour?->trade ?? 'Labour' }}
                                @if ($attendance->labour?->mobile)
                                    <br>{{ $attendance->labour->mobile }}
                                @endif
                            </div>
                        </td>
                        <td>
                            @if ($attendance->photo_path)
                                <a href="{{ route('admin.labour-attendance.photo', $attendance) }}" target="_blank">
                                    <img class="thumb" src="{{ route('admin.labour-attendance.photo', $attendance) }}" alt="Labour attendance photo">
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $attendance->status }}">
                                {{ str_replace('_', ' ', $attendance->status) }}
                            </span>
                            <div class="table-subtext">
                                Hours: {{ $attendance->work_hours ?? '-' }}
                                @if ($attendance->remarks)
                                    <br>{{ $attendance->remarks }}
                                @endif
                            </div>
                        </td>
                        <td>
                            {{ $attendance->engineer?->name ?? '-' }}
                            @if ($attendance->engineer?->mobile)
                                <div class="table-subtext">{{ $attendance->engineer->mobile }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $attendance->approval_status }}">
                                {{ $attendance->approval_status }}
                            </span>
                            @if ($attendance->admin_note)
                                <div class="table-subtext">Admin note: {{ $attendance->admin_note }}</div>
                            @endif
                        </td>
                        <td>
                            <form class="inline-status-form" method="POST" action="{{ route('admin.labour-attendance.update', $attendance) }}">
                                @csrf
                                @method('PATCH')
                                <select name="approval_status" required>
                                    @foreach ($approvalStatuses as $approvalStatus)
                                        <option value="{{ $approvalStatus }}" @selected($attendance->approval_status === $approvalStatus)>
                                            {{ ucfirst($approvalStatus) }}
                                        </option>
                                    @endforeach
                                </select>
                                <textarea name="admin_note" placeholder="Admin note">{{ $attendance->admin_note }}</textarea>
                                <button class="btn" type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="9">No labour attendance entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $attendances->links() }}
    </div>
@endsection
