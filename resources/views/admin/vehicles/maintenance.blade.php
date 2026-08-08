@extends('admin.layouts.app')

@section('title', 'Fleet Maintenance | Admin Panel')
@section('headerTitle', 'Fleet Maintenance')
@section('headerSubtitle', 'Service schedules, breakdown/idle time, job cards, repair cost and cost per hour')

@section('content')
    <style>
        .fleet-maintenance-page {
            display: grid;
            gap: 18px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .fleet-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px 18px;
        }

        .fleet-grid .wide {
            grid-column: span 2;
        }

        .fleet-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .fleet-table th,
        .fleet-table td {
            border: 1px solid #d7e3f2;
            padding: 8px;
            vertical-align: top;
            font-size: 13px;
        }

        .fleet-table th {
            background: #f8fbff;
            color: #526b91;
            font-size: 12px;
            text-transform: uppercase;
            text-align: left;
        }

        .fleet-table input,
        .fleet-table select,
        .fleet-table textarea,
        .fleet-grid input,
        .fleet-grid select,
        .fleet-grid textarea,
        .fleet-filter input,
        .fleet-filter select {
            width: 100%;
            min-width: 0;
            border: 1px solid #c9d7e8;
            border-radius: 7px;
            padding: 9px 10px;
        }

        .fleet-table textarea,
        .fleet-grid textarea {
            min-height: 64px;
            resize: vertical;
        }

        .fleet-actions {
            display: grid;
            gap: 8px;
        }

        .fleet-actions .btn {
            width: 100%;
        }

        .fleet-filter {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }

        @media (max-width: 1200px) {
            .fleet-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .fleet-filter {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .fleet-grid,
            .fleet-filter {
                grid-template-columns: 1fr;
            }

            .fleet-grid .wide {
                grid-column: span 1;
            }
        }
    </style>

    @php
        $recordTypes = ['service' => 'Service', 'breakdown' => 'Breakdown', 'idle' => 'Idle', 'job_card' => 'Job Card', 'repair' => 'Repair', 'depreciation' => 'Depreciation'];
        $statuses = ['open' => 'Open', 'scheduled' => 'Scheduled', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    @endphp

    <div class="fleet-maintenance-page">
        <div class="page-head">
            <div>
                <h1>Fleet Maintenance</h1>
                <p>Track service due dates, breakdown/idle hours, job cards, repairs, depreciation and cost per hour.</p>
            </div>
        </div>

        @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert-error">{{ session('error') }}</div> @endif
        @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

        <section class="stats-grid">
            <div class="card stat-card"><span>Total Records</span><strong>{{ $summary['total_records'] }}</strong></div>
            <div class="card stat-card"><span>Open Jobs</span><strong>{{ $summary['open_jobs'] }}</strong></div>
            <div class="card stat-card"><span>Breakdown Hours</span><strong>{{ number_format($summary['breakdown_hours'], 2) }}</strong></div>
            <div class="card stat-card"><span>Total Cost</span><strong>{{ number_format($summary['total_cost'], 2) }}</strong></div>
        </section>

        <form class="card form-card fleet-filter" method="GET" action="{{ route('admin.vehicle-maintenance.index') }}">
            <label>Vehicle
                <select name="vehicle_id">
                    <option value="">All vehicles</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected((string) ($filters['vehicle_id'] ?? '') === (string) $vehicle->id)>{{ $vehicle->vehicle_number }} {{ $vehicle->vehicle_type ? '- '.$vehicle->vehicle_type : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label>Type
                <select name="record_type">
                    <option value="">All types</option>
                    @foreach ($recordTypes as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['record_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
        </form>

        <form class="card form-card" method="POST" action="{{ route('admin.vehicle-maintenance.store') }}">
            @csrf
            <section class="form-section">
                <h2 class="section-title">Add Maintenance / Job Card</h2>
                <div class="fleet-grid">
                    <label>Vehicle
                        <select name="vehicle_id" required>
                            <option value="">Select vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id', $filters['vehicle_id'] ?? '') === (string) $vehicle->id)>{{ $vehicle->vehicle_number }} {{ $vehicle->vehicle_type ? '- '.$vehicle->vehicle_type : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Type
                        <select name="record_type">
                            @foreach ($recordTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('record_type', 'service') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Job Card No.<input name="job_card_no" value="{{ old('job_card_no') }}"></label>
                    <label>Date<input name="record_date" type="date" value="{{ old('record_date', now()->toDateString()) }}" required></label>
                    <label>Next Service Date<input name="next_service_date" type="date" value="{{ old('next_service_date') }}"></label>
                    <label>Meter Reading<input name="meter_reading" type="number" min="0" step="0.01" value="{{ old('meter_reading', 0) }}"></label>
                    <label>Idle Hours<input name="idle_hours" type="number" min="0" step="0.01" value="{{ old('idle_hours', 0) }}"></label>
                    <label>Breakdown Hours<input name="breakdown_hours" type="number" min="0" step="0.01" value="{{ old('breakdown_hours', 0) }}"></label>
                    <label>Service Cost<input name="service_cost" type="number" min="0" step="0.01" value="{{ old('service_cost', 0) }}"></label>
                    <label>Repair Cost<input name="repair_cost" type="number" min="0" step="0.01" value="{{ old('repair_cost', 0) }}"></label>
                    <label>Fuel Cost<input name="fuel_cost" type="number" min="0" step="0.01" value="{{ old('fuel_cost', 0) }}"></label>
                    <label>Depreciation Cost<input name="depreciation_cost" type="number" min="0" step="0.01" value="{{ old('depreciation_cost', 0) }}"></label>
                    <label>Working Hours<input name="working_hours" type="number" min="0" step="0.01" value="{{ old('working_hours', 0) }}"></label>
                    <label>Status
                        <select name="status">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'open') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="wide">Description<textarea name="description">{{ old('description') }}</textarea></label>
                    <label class="wide">Remarks<textarea name="remarks">{{ old('remarks') }}</textarea></label>
                </div>
            </section>
            <button class="btn" type="submit">Save Record</button>
        </form>

        <section>
            <h2>Maintenance Register</h2>
            <div class="card table-card">
                <table class="fleet-table">
                    <thead>
                        <tr>
                            <th style="width: 12%">Vehicle</th>
                            <th style="width: 12%">Type / Job</th>
                            <th style="width: 13%">Dates</th>
                            <th style="width: 13%">Hours</th>
                            <th style="width: 16%">Costs</th>
                            <th style="width: 18%">Details</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 8%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            @php($formId = 'fleet-maintenance-'.$record->id)
                            <tr>
                                <td>
                                    <select form="{{ $formId }}" name="vehicle_id">
                                        @foreach ($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}" @selected((int) $record->vehicle_id === (int) $vehicle->id)>{{ $vehicle->vehicle_number }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select form="{{ $formId }}" name="record_type">
                                        @foreach ($recordTypes as $value => $label)
                                            <option value="{{ $value }}" @selected($record->record_type === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input form="{{ $formId }}" name="job_card_no" value="{{ $record->job_card_no }}" placeholder="Job card">
                                    <input form="{{ $formId }}" name="meter_reading" type="number" min="0" step="0.01" value="{{ number_format((float) $record->meter_reading, 2, '.', '') }}" placeholder="Meter">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="record_date" type="date" value="{{ $record->record_date?->toDateString() }}">
                                    <input form="{{ $formId }}" name="next_service_date" type="date" value="{{ $record->next_service_date?->toDateString() }}">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="idle_hours" type="number" min="0" step="0.01" value="{{ number_format((float) $record->idle_hours, 2, '.', '') }}" placeholder="Idle">
                                    <input form="{{ $formId }}" name="breakdown_hours" type="number" min="0" step="0.01" value="{{ number_format((float) $record->breakdown_hours, 2, '.', '') }}" placeholder="Breakdown">
                                    <input form="{{ $formId }}" name="working_hours" type="number" min="0" step="0.01" value="{{ number_format((float) $record->working_hours, 2, '.', '') }}" placeholder="Working">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="service_cost" type="number" min="0" step="0.01" value="{{ number_format((float) $record->service_cost, 2, '.', '') }}" placeholder="Service">
                                    <input form="{{ $formId }}" name="repair_cost" type="number" min="0" step="0.01" value="{{ number_format((float) $record->repair_cost, 2, '.', '') }}" placeholder="Repair">
                                    <input form="{{ $formId }}" name="fuel_cost" type="number" min="0" step="0.01" value="{{ number_format((float) $record->fuel_cost, 2, '.', '') }}" placeholder="Fuel">
                                    <input form="{{ $formId }}" name="depreciation_cost" type="number" min="0" step="0.01" value="{{ number_format((float) $record->depreciation_cost, 2, '.', '') }}" placeholder="Depreciation">
                                    <div class="table-subtext">Total {{ number_format((float) $record->total_cost, 2) }} | /Hr {{ number_format((float) $record->cost_per_hour, 2) }}</div>
                                </td>
                                <td>
                                    <textarea form="{{ $formId }}" name="description" placeholder="Description">{{ $record->description }}</textarea>
                                    <textarea form="{{ $formId }}" name="remarks" placeholder="Remarks">{{ $record->remarks }}</textarea>
                                </td>
                                <td>
                                    <select form="{{ $formId }}" name="status">
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected($record->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="fleet-actions">
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.vehicle-maintenance.update', $record) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <button class="btn small" form="{{ $formId }}" type="submit">Update</button>
                                        <form method="POST" action="{{ route('admin.vehicle-maintenance.destroy', $record) }}" onsubmit="return confirm('Delete this maintenance record?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger small" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No fleet maintenance record added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $records->links('admin.pagination') }}</div>
        </section>
    </div>
@endsection
