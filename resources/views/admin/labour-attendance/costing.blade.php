@extends('admin.layouts.app')

@section('title', 'Labour Costing | Admin Panel')
@section('headerTitle', 'Labour Costing')
@section('headerSubtitle', 'Muster payroll, overtime, wage type and work-category allocation')

@section('content')
    <style>
        .labour-costing-page { display: grid; gap: 18px; max-width: 100%; overflow-x: hidden; }
        .costing-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px 18px; }
        .costing-grid .wide { grid-column: span 2; }
        .costing-filter { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; align-items: end; }
        .costing-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .costing-table th, .costing-table td { border: 1px solid #d7e3f2; padding: 8px; vertical-align: top; font-size: 13px; }
        .costing-table th { background: #f8fbff; color: #526b91; font-size: 12px; text-transform: uppercase; text-align: left; }
        .costing-table input, .costing-table select, .costing-table textarea,
        .costing-grid input, .costing-grid select, .costing-grid textarea,
        .costing-filter input, .costing-filter select {
            width: 100%; min-width: 0; border: 1px solid #c9d7e8; border-radius: 7px; padding: 9px 10px;
        }
        .costing-table textarea, .costing-grid textarea { min-height: 62px; resize: vertical; }
        .costing-actions { display: grid; gap: 8px; }
        .costing-actions .btn { width: 100%; }
        @media (max-width: 1200px) {
            .costing-grid, .costing-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 760px) {
            .costing-grid, .costing-filter { grid-template-columns: 1fr; }
            .costing-grid .wide { grid-column: span 1; }
        }
    </style>

    @php
        $labourTypes = ['daily_wage' => 'Daily Wage', 'permanent' => 'Permanent'];
        $shifts = ['day' => 'Day', 'night' => 'Night', 'general' => 'General'];
        $statuses = ['draft' => 'Draft', 'approved' => 'Approved', 'paid' => 'Paid'];
    @endphp

    <div class="labour-costing-page">
        <div class="page-head">
            <div>
                <h1>Labour Costing</h1>
                <p>Allocate labour by category and calculate muster payroll with overtime.</p>
            </div>
        </div>

        @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert-error">{{ session('error') }}</div> @endif
        @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

        <section class="stats-grid">
            <div class="card stat-card"><span>Muster Records</span><strong>{{ $summary['records'] }}</strong></div>
            <div class="card stat-card"><span>Payable Days</span><strong>{{ number_format($summary['payable_days'], 2) }}</strong></div>
            <div class="card stat-card"><span>Overtime Hours</span><strong>{{ number_format($summary['overtime_hours'], 2) }}</strong></div>
            <div class="card stat-card"><span>Payroll Cost</span><strong>{{ number_format($summary['total_amount'], 2) }}</strong></div>
        </section>

        <form class="card form-card costing-filter" method="GET" action="{{ route('admin.labour-costing.index') }}">
            <label>From Date<input name="from_date" type="date" value="{{ $fromDate }}"></label>
            <label>To Date<input name="to_date" type="date" value="{{ $toDate }}"></label>
            <label>Site
                <select name="labour_site_id">
                    <option value="">All sites</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}" @selected((string) ($filters['labour_site_id'] ?? '') === (string) $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Contractor
                <select name="contractor_id">
                    <option value="">All contractors</option>
                    @foreach ($contractors as $contractor)
                        <option value="{{ $contractor->id }}" @selected((string) ($filters['contractor_id'] ?? '') === (string) $contractor->id)>{{ $contractor->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Category<input name="work_category" value="{{ $filters['work_category'] ?? '' }}" placeholder="Mason, Helper"></label>
            <button class="btn" type="submit">Filter</button>
        </form>

        <form class="card form-card" method="POST" action="{{ route('admin.labour-costing.store') }}">
            @csrf
            <section class="form-section">
                <h2 class="section-title">Add Muster Costing Row</h2>
                <div class="costing-grid">
                    <label>Labour
                        <select id="costing_labour_id" name="labour_id" required>
                            <option value="">Select labour</option>
                            @foreach ($labours as $labour)
                                <option
                                    value="{{ $labour->id }}"
                                    data-contractor="{{ $labour->contractor_id }}"
                                    data-type="{{ $labour->labour_type ?? 'daily_wage' }}"
                                    data-category="{{ $labour->work_category }}"
                                    data-wage="{{ $labour->daily_wage_rate }}"
                                    data-ot="{{ $labour->overtime_rate }}"
                                >{{ $labour->name }}{{ $labour->labour_code ? ' - '.$labour->labour_code : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Site
                        <select name="labour_site_id">
                            <option value="">Select site</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Contractor
                        <select id="costing_contractor_id" name="contractor_id">
                            <option value="">Select contractor</option>
                            @foreach ($contractors as $contractor)
                                <option value="{{ $contractor->id }}">{{ $contractor->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Date<input name="work_date" type="date" value="{{ now()->toDateString() }}" required></label>
                    <label>Labour Type
                        <select id="costing_labour_type" name="labour_type">
                            @foreach ($labourTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Shift
                        <select name="shift">
                            @foreach ($shifts as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Work Category<input id="costing_work_category" name="work_category" placeholder="Mason, Helper, Steel fixing"></label>
                    <label>Payable Days<input name="payable_days" type="number" min="0" step="0.01" value="1"></label>
                    <label>Work Hours<input name="work_hours" type="number" min="0" step="0.01" value="8"></label>
                    <label>Overtime Hours<input name="overtime_hours" type="number" min="0" step="0.01" value="0"></label>
                    <label>Daily Wage Rate<input id="costing_daily_wage_rate" name="daily_wage_rate" type="number" min="0" step="0.01" value="0"></label>
                    <label>Overtime Rate<input id="costing_overtime_rate" name="overtime_rate" type="number" min="0" step="0.01" value="0"></label>
                    <label>Status
                        <select name="status">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="wide">Remarks<textarea name="remarks"></textarea></label>
                </div>
            </section>
            <button class="btn" type="submit">Save Costing</button>
        </form>

        <section>
            <h2>Muster Payroll Register</h2>
            <div class="card table-card">
                <table class="costing-table">
                    <thead>
                        <tr>
                            <th style="width: 12%">Date / Site</th>
                            <th style="width: 16%">Labour</th>
                            <th style="width: 13%">Category</th>
                            <th style="width: 13%">Days / Hours</th>
                            <th style="width: 15%">Rates</th>
                            <th style="width: 13%">Amount</th>
                            <th style="width: 9%">Status</th>
                            <th style="width: 9%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            @php($formId = 'labour-costing-'.$record->id)
                            <tr>
                                <td>
                                    <input form="{{ $formId }}" name="work_date" type="date" value="{{ $record->work_date?->toDateString() }}">
                                    <select form="{{ $formId }}" name="labour_site_id">
                                        <option value="">Site</option>
                                        @foreach ($sites as $site)
                                            <option value="{{ $site->id }}" @selected((int) $record->labour_site_id === (int) $site->id)>{{ $site->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select form="{{ $formId }}" name="labour_id">
                                        @foreach ($labours as $labour)
                                            <option value="{{ $labour->id }}" @selected((int) $record->labour_id === (int) $labour->id)>{{ $labour->name }}</option>
                                        @endforeach
                                    </select>
                                    <select form="{{ $formId }}" name="contractor_id">
                                        <option value="">Contractor</option>
                                        @foreach ($contractors as $contractor)
                                            <option value="{{ $contractor->id }}" @selected((int) $record->contractor_id === (int) $contractor->id)>{{ $contractor->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select form="{{ $formId }}" name="labour_type">
                                        @foreach ($labourTypes as $value => $label)
                                            <option value="{{ $value }}" @selected($record->labour_type === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select form="{{ $formId }}" name="shift">
                                        @foreach ($shifts as $value => $label)
                                            <option value="{{ $value }}" @selected($record->shift === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input form="{{ $formId }}" name="work_category" value="{{ $record->work_category }}" placeholder="Category">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="payable_days" type="number" min="0" step="0.01" value="{{ number_format((float) $record->payable_days, 2, '.', '') }}">
                                    <input form="{{ $formId }}" name="work_hours" type="number" min="0" step="0.01" value="{{ number_format((float) $record->work_hours, 2, '.', '') }}">
                                    <input form="{{ $formId }}" name="overtime_hours" type="number" min="0" step="0.01" value="{{ number_format((float) $record->overtime_hours, 2, '.', '') }}">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="daily_wage_rate" type="number" min="0" step="0.01" value="{{ number_format((float) $record->daily_wage_rate, 2, '.', '') }}">
                                    <input form="{{ $formId }}" name="overtime_rate" type="number" min="0" step="0.01" value="{{ number_format((float) $record->overtime_rate, 2, '.', '') }}">
                                </td>
                                <td>
                                    Base {{ number_format((float) $record->base_amount, 2) }}
                                    <div class="table-subtext">OT {{ number_format((float) $record->overtime_amount, 2) }}</div>
                                    <strong>{{ number_format((float) $record->total_amount, 2) }}</strong>
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
                                    <div class="costing-actions">
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.labour-costing.update', $record) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <button class="btn small" form="{{ $formId }}" type="submit">Update</button>
                                        <form method="POST" action="{{ route('admin.labour-costing.destroy', $record) }}" onsubmit="return confirm('Delete this labour costing row?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger small" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No labour costing record added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $records->links('admin.pagination') }}</div>
        </section>
    </div>

    <script>
        const labourSelect = document.getElementById('costing_labour_id');
        if (labourSelect) {
            labourSelect.addEventListener('change', () => {
                const selected = labourSelect.selectedOptions[0];
                if (!selected || !selected.value) return;
                document.getElementById('costing_contractor_id').value = selected.dataset.contractor || '';
                document.getElementById('costing_labour_type').value = selected.dataset.type || 'daily_wage';
                document.getElementById('costing_work_category').value = selected.dataset.category || '';
                document.getElementById('costing_daily_wage_rate').value = selected.dataset.wage || 0;
                document.getElementById('costing_overtime_rate').value = selected.dataset.ot || 0;
            });
        }
    </script>
@endsection
