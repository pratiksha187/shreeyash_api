@extends('admin.layouts.app')

@section('title', 'Challans | Admin Panel')
@section('headerTitle', 'Challans')
@section('headerSubtitle', 'Submitted delivery challan records')

@section('content')
    <div class="page-header">
        <div>
            <h1>Challans</h1>
            <p>Review saved challan records submitted from the mobile app.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.challans.export', request()->query()) }}">Export CSV</a>
    </div>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.challans.index') }}">
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
                    <label for="user_id">Submitted By</label>
                    <select id="user_id" name="user_id">
                        <option value="">All Employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $employee->id)>
                                {{ $employee->name }}{{ $employee->mobile ? ' - ' . $employee->mobile : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="location">Location</label>
                    <select id="location" name="location">
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
                    <label for="vehicle_no">Vehicle No.</label>
                    <select id="vehicle_no" name="vehicle_no">
                        <option value="">All Vehicles</option>
                        @foreach ($vehicles as $vehicleNo)
                            <option value="{{ $vehicleNo }}" @selected(($filters['vehicle_no'] ?? '') === $vehicleNo)>{{ $vehicleNo }}</option>
                        @endforeach
                    </select>
                    @error('vehicle_no')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="search">Search</label>
                    <input id="search" name="search" value="{{ old('search', $filters['search'] ?? '') }}" placeholder="Challan, party, material, receiver">
                    @error('search')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Apply Filter</button>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <a class="btn secondary" href="{{ route('admin.challans.index') }}">Clear</a>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total Challans</span>
            <strong>{{ $summary['total_challans'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Employees</span>
            <strong>{{ $summary['employees'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Vehicles</span>
            <strong>{{ $summary['vehicles'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Latest Date</span>
            <strong>{{ $summary['latest_date'] ? \Carbon\Carbon::parse($summary['latest_date'])->format('d M') : '-' }}</strong>
        </div>
    </section>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Challan No.</th>
                    <th>Date</th>
                    <th>Name Of Party</th>
                    <th>Material / M/c</th>
                    <th>Vehicle / Measurement</th>
                    <th>Location / Time</th>
                    <th>Receiver / Driver</th>
                    <th>Submitted By</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($challans as $challan)
                    <tr>
                        <td>{{ $challan->challan_no }}</td>
                        <td>{{ $challan->challan_date?->format('d/m/Y') }}</td>
                        <td class="text-wrap">{{ $challan->party_name }}</td>
                        <td class="text-wrap">{{ $challan->material_machine }}</td>
                        <td>
                            {{ $challan->vehicle_no }}
                            <div class="table-subtext">{{ $challan->measurement }}</div>
                        </td>
                        <td>
                            {{ $challan->location }}
                            <div class="table-subtext">{{ $challan->delivery_time }}</div>
                        </td>
                        <td>
                            {{ $challan->receiver_name }}
                            <div class="table-subtext">Driver: {{ $challan->driver_name }}</div>
                        </td>
                        <td>
                            @if ($challan->user)
                                <a class="table-link" href="{{ route('admin.employees.show', $challan->user) }}">
                                    {{ $challan->user->name }}
                                </a>
                                <div class="table-subtext">
                                    {{ $challan->user->designation ?? 'Employee' }}
                                    @if ($challan->user->mobile)
                                        <br>{{ $challan->user->mobile }}
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <a class="table-link" href="{{ route('admin.challans.download', $challan) }}">
                                {{ $challan->pdf_file_path ? 'Download PDF' : 'Generate PDF' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="9">No challans found for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $challans->links() }}
    </div>
@endsection
