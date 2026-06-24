@extends('admin.layouts.app')

@section('title', $vehicle->vehicle_number . ' | Admin Panel')
@section('bodyClass', 'vehicle-show-page')
@section('headerTitle', 'Vehicle Details')
@section('headerSubtitle', 'Calendar vehicle in and out view')

@section('content')
    @php
        $timeParts = function (?string $time): array {
            if (! $time) {
                return ['hour' => '', 'minute' => '', 'period' => ''];
            }

            try {
                $parsed = \Carbon\Carbon::createFromFormat('H:i', $time);
            } catch (\Throwable $exception) {
                return ['hour' => '', 'minute' => '', 'period' => ''];
            }

            return [
                'hour' => $parsed->format('h'),
                'minute' => $parsed->format('i'),
                'period' => $parsed->format('A'),
            ];
        };

        $timeLabel = function (?string $time) use ($timeParts): string {
            $parts = $timeParts($time);

            if (! $parts['hour'] || ! $parts['minute'] || ! $parts['period']) {
                return '';
            }

            return $parts['hour'].':'.$parts['minute'].' '.$parts['period'];
        };
    @endphp

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
            <span>Default Site</span>
            <strong>{{ $vehicle->default_site ?? '-' }}</strong>
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
            <span>Per Day Rate</span>
            <strong>{{ number_format((float) $vehicle->hire_per_day_rate, 2) }}</strong>
        </div>
        <div class="card detail-item">
            <span>Per Hour Rate</span>
            <strong>{{ number_format((float) $vehicle->hire_per_hour_rate, 2) }}</strong>
        </div>
        <div class="card detail-item">
            <span>GST</span>
            <strong>{{ number_format((float) $vehicle->gst_percentage, 2) }}%</strong>
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
            <span>Duty Hrs</span>
            <strong>{{ $billingSummary['total_duty_hours'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Hire Amount</span>
            <strong>{{ number_format($billingSummary['hire_total_amount'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Net Payable</span>
            <strong>{{ number_format($billingSummary['net_payable'], 2) }}</strong>
        </div>
    </section>

    <div class="page-header">
        <div>
            <h1>{{ $monthLabel }} Vehicle Sheet</h1>
            <p>Daily duty hours, hourly amount, site, diesel, and bill totals are calculated automatically.</p>
        </div>
    </div>

    <form id="monthly-entry-form" class="card table-wrap" method="POST" action="{{ route('admin.vehicles.logs.monthly', $vehicle) }}" data-hour-rate="{{ (float) $vehicle->hire_per_hour_rate }}">
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
                    <th>Value By Total Hrs</th>
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
                            <input class="js-in-time" data-time-value name="entries[{{ $entryKey }}][in_time]" type="hidden" value="{{ $row['in_time_value'] }}">
                            <span class="admin-time-text-wrap">
                                <input class="admin-time-text" data-time-display list="vehicle-time-options" type="text" value="{{ $timeLabel($row['in_time_value']) }}" placeholder="hh:mm AM" inputmode="text" aria-label="In time">
                            </span>
                        </td>
                        <td>
                            <input class="js-out-time" data-time-value name="entries[{{ $entryKey }}][out_time]" type="hidden" value="{{ $row['out_time_value'] }}">
                            <span class="admin-time-text-wrap">
                                <input class="admin-time-text" data-time-display list="vehicle-time-options" type="text" value="{{ $timeLabel($row['out_time_value']) }}" placeholder="hh:mm AM" inputmode="text" aria-label="Out time">
                            </span>
                        </td>
                        <td>
                            <input class="js-total-hrs" type="text" value="{{ $row['total_hours'] }}" readonly>
                        </td>
                        <td>
                            <input class="sheet-number js-hire-hours" type="number" value="{{ number_format($row['hire_hours'], 2, '.', '') }}" readonly>
                            <input class="js-hour-rate" type="hidden" value="{{ number_format($billingSummary['hire_per_hour_rate'], 2, '.', '') }}">
                            <input class="js-hire-amount" type="hidden" value="{{ number_format($row['hire_amount'], 2, '.', '') }}">
                            <input name="entries[{{ $entryKey }}][site_name]" type="hidden" value="{{ $row['site_name'] ?: $vehicle->default_site }}">
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

        <datalist id="vehicle-time-options">
            @for ($minuteOffset = 0; $minuteOffset < 1440; $minuteOffset++)
                <option value="{{ \Carbon\Carbon::createFromTime(0, 0)->addMinutes($minuteOffset)->format('h:i A') }}"></option>
            @endfor
        </datalist>

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
                        <th>Duty Hrs</th>
                        <td>{{ $billingSummary['total_duty_hours'] }}</td>
                    </tr>
                    <tr>
                        <th>Value By Total Hrs</th>
                        <td>{{ number_format($billingSummary['total_hire_hours'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Per Day Rate</th>
                        <td>{{ number_format($billingSummary['hire_per_day_rate'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Per Hour Rate</th>
                        <td>{{ number_format($billingSummary['hire_per_hour_rate'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Total</th>
                        <td>{{ number_format($billingSummary['hire_total_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Extra Sunday Paid</th>
                        <td>{{ number_format($billingSummary['extra_sunday_paid_amount'], 2) }}</td>
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
                        <th>Gross Billing Amount</th>
                        <td>{{ number_format($billingSummary['gross_billing_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>{{ number_format($billingSummary['gst_percentage'], 2) }}% GST</th>
                        <td>{{ number_format($billingSummary['gst_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Billing Amount With GST</th>
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
        const monthlyEntryForm = document.getElementById('monthly-entry-form');
        const defaultHourRate = parseFloat(monthlyEntryForm?.dataset.hourRate || 0);

        function parseDisplayTime(value) {
            const normalized = (value || '').trim().toUpperCase().replace(/\s+/g, ' ');

            if (!normalized) {
                return null;
            }

            const match = normalized.match(/^(\d{1,2}):(\d{2})\s?(AM|PM)$/);

            if (!match) {
                return null;
            }

            let hour = parseInt(match[1], 10);
            const minute = parseInt(match[2], 10);
            const period = match[3];

            if (hour < 1 || hour > 12 || minute < 0 || minute > 59) {
                return null;
            }

            if (period === 'AM' && hour === 12) {
                hour = 0;
            }

            if (period === 'PM' && hour !== 12) {
                hour += 12;
            }

            return String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0');
        }

        function formatDisplayTime(value) {
            const hiddenValue = parseDisplayTime(value);

            if (!hiddenValue) {
                return value;
            }

            const parts = hiddenValue.split(':').map(Number);
            const period = parts[0] >= 12 ? 'PM' : 'AM';
            let hour = parts[0] % 12;

            if (hour === 0) {
                hour = 12;
            }

            return String(hour).padStart(2, '0') + ':' + String(parts[1]).padStart(2, '0') + ' ' + period;
        }

        function syncTimeText(displayInput, shouldFormat) {
            const cell = displayInput.closest('td');
            const hiddenInput = cell ? cell.querySelector('[data-time-value]') : null;

            if (!hiddenInput) {
                return;
            }

            hiddenInput.value = parseDisplayTime(displayInput.value) || '';

            if (shouldFormat && hiddenInput.value) {
                displayInput.value = formatDisplayTime(displayInput.value);
            }

            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        document.querySelectorAll('[data-time-display]').forEach((displayInput) => {
            displayInput.addEventListener('input', () => syncTimeText(displayInput, false));
            displayInput.addEventListener('change', () => syncTimeText(displayInput, true));
            displayInput.addEventListener('blur', () => syncTimeText(displayInput, true));
        });

        document.querySelectorAll('[data-entry-row]').forEach((row) => {
            const startReading = row.querySelector('.js-start-reading');
            const endReading = row.querySelector('.js-end-reading');
            const totalKm = row.querySelector('.js-total-km');
            const inTime = row.querySelector('.js-in-time');
            const outTime = row.querySelector('.js-out-time');
            const totalHrs = row.querySelector('.js-total-hrs');
            const hireHours = row.querySelector('.js-hire-hours');
            const hourRate = row.querySelector('.js-hour-rate');
            const hireAmount = row.querySelector('.js-hire-amount');
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
                    hireHours.value = '0.00';
                    hireAmount.value = '0.00';
                    otHrs.value = '00:00';
                    return;
                }

                if (endMinutes < startMinutes) {
                    endMinutes += 1440;
                }

                const totalMinutes = Math.max(0, endMinutes - startMinutes);
                const totalHireHours = totalMinutes / 60;
                const effectiveHourRate = parseFloat(hourRate.value || defaultHourRate || 0);
                totalHrs.value = formatMinutes(totalMinutes);
                hireHours.value = totalHireHours.toFixed(2);
                hireAmount.value = (totalHireHours * effectiveHourRate).toFixed(2);
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
