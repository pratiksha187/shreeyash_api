@extends('admin.layouts.app')

@section('title', $vehicle->vehicle_number . ' | Admin Panel')
@section('headerTitle', 'Vehicle Details')
@section('headerSubtitle', 'Calendar vehicle in and out view')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $vehicle->vehicle_number }}</h1>
            <p>{{ $vehicle->vehicle_type ?? 'Vehicle' }} records for {{ $monthLabel }}.</p>
        </div>
        <div class="actions" style="margin-top: 0;">
            <a class="btn secondary" href="{{ route('admin.vehicles.index') }}">Back to Vehicles</a>
            <a class="btn" href="{{ route('admin.vehicles.edit', $vehicle) }}">Edit Vehicle</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    <section class="detail-grid">
        <div class="card detail-item">
            <span>Vehicle Type</span>
            <strong>{{ $vehicle->vehicle_type ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Owner</span>
            <strong>{{ $vehicle->owner_name ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Default Driver</span>
            <strong>{{ $vehicle->driver_name ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Driver Mobile</span>
            <strong>{{ $vehicle->driver_mobile ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Fixed Amount</span>
            <strong>{{ number_format((float) $vehicle->fixed_monthly_amount, 2) }}</strong>
        </div>
        <div class="card detail-item">
            <span>OT Rate</span>
            <strong>{{ number_format((float) $vehicle->ot_rate, 2) }}</strong>
        </div>
        <div class="card detail-item">
            <span>TDS</span>
            <strong>{{ number_format((float) $vehicle->tds_percentage, 2) }}%</strong>
        </div>
    </section>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.vehicles.show', $vehicle) }}">
        <section class="form-section">
            <h2 class="section-title">Calendar Filter</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="month">Month</label>
                    <input id="month" name="month" type="month" value="{{ old('month', $selectedMonth) }}">
                    @error('month')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Show Calendar</button>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total Records</span>
            <strong>{{ $summary['total_records'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Total KM</span>
            <strong>{{ number_format($summary['total_km'], 0) }}</strong>
        </div>
        <div class="card stat-card">
            <span>OT Hrs</span>
            <strong>{{ $billingSummary['ot_hours'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Net Payable</span>
            <strong>{{ number_format($billingSummary['net_payable'], 2) }}</strong>
        </div>
    </section>

    <div class="page-header">
        <div>
            <h1>{{ $monthLabel }} Vehicle Sheet</h1>
            <p>Daily readings, time, OT, diesel, and billing totals are calculated automatically.</p>
        </div>
    </div>

    <form id="monthly-entry-form" class="card table-wrap" method="POST" action="{{ route('admin.vehicles.logs.monthly', $vehicle) }}">
        @csrf
        <input name="month" type="hidden" value="{{ $selectedMonth }}">

        <table class="vehicle-sheet-table editable-sheet">
            <thead>
                <tr>
                    <th>Sr No</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Challan No</th>
                    <th>Diesel Added</th>
                    <th>Start Reading</th>
                    <th>End Reading</th>
                    <th>Total KM</th>
                    <th>In Time</th>
                    <th>Out Time</th>
                    <th>Total Hrs</th>
                    <th>OT Hrs</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($calendarRows as $row)
                    @php
                        $entryKey = $row['date']->toDateString();
                    @endphp
                    <tr data-entry-row>
                        <td>{{ $row['sr_no'] }}</td>
                        <td>
                            {{ $row['date']->format('d/m/Y') }}
                            <input name="entries[{{ $entryKey }}][log_id]" type="hidden" value="{{ $row['log_id'] }}">
                            <input name="entries[{{ $entryKey }}][entry_date]" type="hidden" value="{{ $entryKey }}">
                        </td>
                        <td>{{ $row['day'] }}</td>
                        <td>
                            <input name="entries[{{ $entryKey }}][challan_no]" type="text" value="{{ $row['challan_no'] }}">
                        </td>
                        <td>
                            <input class="sheet-number" name="entries[{{ $entryKey }}][diesel_added]" type="number" min="0" step="0.01" value="{{ number_format($row['diesel_added'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="sheet-number js-start-reading" name="entries[{{ $entryKey }}][start_reading]" type="number" min="0" step="0.01" value="{{ number_format($row['start_reading'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="sheet-number js-end-reading" name="entries[{{ $entryKey }}][end_reading]" type="number" min="0" step="0.01" value="{{ number_format($row['end_reading'], 2, '.', '') }}">
                        </td>
                        <td>
                            <input class="sheet-number js-total-km" type="number" value="{{ number_format($row['total_km'], 0, '.', '') }}" readonly>
                        </td>
                        <td>
                            <input class="js-in-time" name="entries[{{ $entryKey }}][in_time]" type="time" value="{{ $row['in_time_value'] }}">
                        </td>
                        <td>
                            <input class="js-out-time" name="entries[{{ $entryKey }}][out_time]" type="time" value="{{ $row['out_time_value'] }}">
                        </td>
                        <td>
                            <input class="js-total-hrs" type="text" value="{{ $row['total_hours'] }}" readonly>
                        </td>
                        <td>
                            <input class="js-ot-hrs" type="text" value="{{ $row['ot_hours'] }}" readonly>
                        </td>
                        <td>
                            <input name="entries[{{ $entryKey }}][remarks]" type="text" value="{{ $row['entry_remarks'] }}">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="sheet-actions">
            <button class="btn" type="submit">Save Daily Entries</button>
            <a class="btn secondary" href="#bill-summary">View Bill</a>
        </div>
    </form>

    <div id="bill-summary" class="sheet-summary-grid">
        <div class="card table-wrap">
            <table>
                <tbody>
                    <tr>
                        <th>Opening Reading</th>
                        <td>{{ number_format($billingSummary['opening_reading'], 0) }}</td>
                    </tr>
                    <tr>
                        <th>Closing Reading</th>
                        <td>{{ number_format($billingSummary['closing_reading'], 0) }}</td>
                    </tr>
                    <tr>
                        <th>Total KM</th>
                        <td>{{ number_format($billingSummary['total_km'], 0) }}</td>
                    </tr>
                    <tr>
                        <th>Diesel Total</th>
                        <td>{{ number_format($billingSummary['diesel_total'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Average</th>
                        <td>{{ number_format($billingSummary['average'], 4) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card table-wrap">
            <table>
                <tbody>
                    <tr>
                        <th>Fixed Monthly Amount</th>
                        <td>{{ number_format($billingSummary['fixed_monthly_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>OT Hrs</th>
                        <td>{{ $billingSummary['ot_hours'] }}</td>
                    </tr>
                    <tr>
                        <th>OT Rate</th>
                        <td>{{ number_format($billingSummary['ot_rate'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Total OT Amount</th>
                        <td>{{ number_format($billingSummary['total_ot_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Total Billing Amount</th>
                        <td>{{ number_format($billingSummary['total_billing_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>TDS {{ number_format($billingSummary['tds_percentage'], 2) }}%</th>
                        <td>{{ number_format($billingSummary['tds_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Net Payable</th>
                        <td><strong>{{ number_format($billingSummary['net_payable'], 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-entry-row]').forEach((row) => {
            const startReading = row.querySelector('.js-start-reading');
            const endReading = row.querySelector('.js-end-reading');
            const totalKm = row.querySelector('.js-total-km');
            const inTime = row.querySelector('.js-in-time');
            const outTime = row.querySelector('.js-out-time');
            const totalHrs = row.querySelector('.js-total-hrs');
            const otHrs = row.querySelector('.js-ot-hrs');

            const formatMinutes = (minutes) => {
                const hours = Math.floor(minutes / 60).toString().padStart(2, '0');
                const mins = (minutes % 60).toString().padStart(2, '0');

                return `${hours}:${mins}`;
            };

            const timeToMinutes = (value) => {
                if (!value) {
                    return null;
                }

                const [hours, minutes] = value.split(':').map(Number);

                return (hours * 60) + minutes;
            };

            const recalculate = () => {
                const start = parseFloat(startReading.value || 0);
                const end = parseFloat(endReading.value || 0);
                totalKm.value = end >= start ? Math.round(end - start) : 0;

                const startMinutes = timeToMinutes(inTime.value);
                let endMinutes = timeToMinutes(outTime.value);

                if (startMinutes === null || endMinutes === null) {
                    totalHrs.value = '00:00';
                    otHrs.value = '00:00';
                    return;
                }

                if (endMinutes < startMinutes) {
                    endMinutes += 1440;
                }

                const totalMinutes = Math.max(0, endMinutes - startMinutes);
                totalHrs.value = formatMinutes(totalMinutes);
                otHrs.value = formatMinutes(Math.max(0, totalMinutes - 720));
            };

            row.querySelectorAll('input').forEach((input) => {
                input.addEventListener('input', recalculate);
                input.addEventListener('change', recalculate);
            });

            recalculate();
        });
    </script>
@endsection
