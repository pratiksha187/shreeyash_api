@extends('admin.layouts.app')

@section('title', 'Missed Requests | Admin Panel')
@section('bodyClass', 'missed-requests-page')
@section('headerTitle', 'Missed Requests')
@section('headerSubtitle', 'Employee missed attendance correction requests')

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
    @endphp

    <div class="page-header">
        <div>
            <h1>Missed Requests</h1>
            <p>Review requests for missed clock in, clock out, or full day attendance.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.missed-requests.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filters</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="from_date">From Date</label>
                    <input id="from_date" name="from_date" type="date" value="{{ old('from_date', $fromDate) }}">
                    @error('from_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="to_date">To Date</label>
                    <input id="to_date" name="to_date" type="date" value="{{ old('to_date', $toDate) }}">
                    @error('to_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="user_id">Employee</label>
                    <select id="user_id" name="user_id">
                        <option value="">All Employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) $selectedUserId === (string) $employee->id)>
                                {{ $employee->name }}{{ $employee->mobile ? ' - ' . $employee->mobile : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="request_for">Request For</label>
                    <select id="request_for" name="request_for">
                        <option value="">All Types</option>
                        @foreach ($requestTypes as $requestType)
                            <option value="{{ $requestType }}" @selected($selectedRequestFor === $requestType)>
                                {{ str_replace('_', ' ', ucfirst($requestType)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('request_for')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="error">{{ $message }}</div>
                    @enderror
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
            <span>Total Requests</span>
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

    <div class="card table-wrap missed-requests-table-wrap">
        <table class="missed-requests-table">
            <colgroup>
                <col class="missed-toggle-column">
                <col class="missed-date-column">
                <col class="missed-employee-column">
                <col class="missed-type-column">
                <col class="missed-reason-column">
                <col class="missed-status-column">
                <col class="missed-submitted-column">
            </colgroup>
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Attendance Date</th>
                    <th>Employee</th>
                    <th>Request For</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $missedRequest)
                    @php
                        $attendance = $attendanceTimes->get(
                            $missedRequest->user_id.'|'.$missedRequest->attendance_date->toDateString()
                        );
                        $showRowErrors = (string) old('request_id') === (string) $missedRequest->id;
                        $checkInTime = $showRowErrors ? old('check_in_time') : $attendance?->localCheckInAt()?->format('H:i');
                        $checkOutTime = $showRowErrors ? old('check_out_time') : $attendance?->localCheckOutAt()?->format('H:i');
                        $checkInParts = $timeParts($checkInTime);
                        $checkOutParts = $timeParts($checkOutTime);
                    @endphp
                    <tr class="missed-summary-row">
                        <td class="missed-toggle-cell">
                            <button
                                class="missed-toggle-button"
                                type="button"
                                aria-expanded="{{ $showRowErrors ? 'true' : 'false' }}"
                                aria-controls="missed-action-{{ $missedRequest->id }}"
                                data-missed-toggle="missed-action-{{ $missedRequest->id }}"
                                title="Update request"
                            >{{ $showRowErrors ? '-' : '+' }}</button>
                        </td>
                        <td>{{ $missedRequest->attendance_date?->format('d M Y') }}</td>
                        <td>
                            @if ($missedRequest->user)
                                <a class="table-link" href="{{ route('admin.employees.show', $missedRequest->user) }}">
                                    {{ $missedRequest->user->name }}
                                </a>
                                <div class="table-subtext">
                                    {{ $missedRequest->user->designation ?? 'Employee' }}
                                    @if ($missedRequest->user->mobile)
                                        <br>{{ $missedRequest->user->mobile }}
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ str_replace('_', ' ', ucfirst($missedRequest->request_for)) }}</td>
                        <td class="text-wrap">
                            {{ $missedRequest->reason }}
                            @if ($missedRequest->admin_note)
                                <div class="table-subtext">Admin note: {{ $missedRequest->admin_note }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $missedRequest->status }}">
                                {{ $missedRequest->status }}
                            </span>
                            @if ($missedRequest->reviewed_at)
                                <div class="table-subtext">
                                    {{ $missedRequest->reviewed_at->format('d M Y h:i A') }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $missedRequest->created_at?->format('d M Y h:i A') }}</td>
                    </tr>
                    <tr
                        id="missed-action-{{ $missedRequest->id }}"
                        class="missed-action-row"
                        @if (! $showRowErrors) hidden @endif
                    >
                        <td colspan="7">
                            <form class="missed-action-form" method="POST" action="{{ route('admin.missed-requests.update', $missedRequest) }}">
                                @csrf
                                @method('PATCH')
                                <input name="request_id" type="hidden" value="{{ $missedRequest->id }}">
                                <label>
                                    Status
                                    <select name="status" required>
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status }}" @selected(($showRowErrors ? old('status') : $missedRequest->status) === $status)>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>
                                    In Time
                                    <input data-time-value name="check_in_time" type="hidden" value="{{ $checkInTime }}">
                                    <span class="admin-time-picker" data-time-picker>
                                        <select data-time-hour aria-label="In time hour">
                                            <option value="">HH</option>
                                            @for ($hour = 1; $hour <= 12; $hour++)
                                                @php($hourValue = str_pad((string) $hour, 2, '0', STR_PAD_LEFT))
                                                <option value="{{ $hourValue }}" @selected($checkInParts['hour'] === $hourValue)>{{ $hourValue }}</option>
                                            @endfor
                                        </select>
                                        <span class="time-separator">:</span>
                                        <select data-time-minute aria-label="In time minute">
                                            <option value="">MM</option>
                                            @for ($minute = 0; $minute <= 59; $minute++)
                                                @php($minuteValue = str_pad((string) $minute, 2, '0', STR_PAD_LEFT))
                                                <option value="{{ $minuteValue }}" @selected($checkInParts['minute'] === $minuteValue)>{{ $minuteValue }}</option>
                                            @endfor
                                        </select>
                                        <select data-time-period aria-label="In time AM or PM">
                                            <option value="">AM/PM</option>
                                            <option value="AM" @selected($checkInParts['period'] === 'AM')>AM</option>
                                            <option value="PM" @selected($checkInParts['period'] === 'PM')>PM</option>
                                        </select>
                                    </span>
                                    @if ($showRowErrors)
                                        @error('check_in_time')
                                            <span class="error">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </label>
                                <label>
                                    Out Time
                                    <input data-time-value name="check_out_time" type="hidden" value="{{ $checkOutTime }}">
                                    <span class="admin-time-picker" data-time-picker>
                                        <select data-time-hour aria-label="Out time hour">
                                            <option value="">HH</option>
                                            @for ($hour = 1; $hour <= 12; $hour++)
                                                @php($hourValue = str_pad((string) $hour, 2, '0', STR_PAD_LEFT))
                                                <option value="{{ $hourValue }}" @selected($checkOutParts['hour'] === $hourValue)>{{ $hourValue }}</option>
                                            @endfor
                                        </select>
                                        <span class="time-separator">:</span>
                                        <select data-time-minute aria-label="Out time minute">
                                            <option value="">MM</option>
                                            @for ($minute = 0; $minute <= 59; $minute++)
                                                @php($minuteValue = str_pad((string) $minute, 2, '0', STR_PAD_LEFT))
                                                <option value="{{ $minuteValue }}" @selected($checkOutParts['minute'] === $minuteValue)>{{ $minuteValue }}</option>
                                            @endfor
                                        </select>
                                        <select data-time-period aria-label="Out time AM or PM">
                                            <option value="">AM/PM</option>
                                            <option value="AM" @selected($checkOutParts['period'] === 'AM')>AM</option>
                                            <option value="PM" @selected($checkOutParts['period'] === 'PM')>PM</option>
                                        </select>
                                    </span>
                                    @if ($showRowErrors)
                                        @error('check_out_time')
                                            <span class="error">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </label>
                                <label class="missed-note-field">
                                    Admin Note
                                    <textarea name="admin_note" placeholder="Admin note">{{ $showRowErrors ? old('admin_note') : $missedRequest->admin_note }}</textarea>
                                </label>
                                <button class="btn missed-update-button" type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No missed attendance requests found for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $requests->links('admin.pagination') }}
    </div>

    <script>
        document.querySelectorAll('[data-missed-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var actionRow = document.getElementById(button.dataset.missedToggle);
                var isOpen = button.getAttribute('aria-expanded') === 'true';

                actionRow.hidden = isOpen;
                button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                button.textContent = isOpen ? '+' : '-';
            });
        });

        function syncTimePicker(picker) {
            var label = picker.closest('label');
            var input = label ? label.querySelector('[data-time-value]') : null;
            var hour = picker.querySelector('[data-time-hour]').value;
            var minute = picker.querySelector('[data-time-minute]').value;
            var period = picker.querySelector('[data-time-period]').value;

            if (! input) {
                return;
            }

            if (! hour || ! minute || ! period) {
                input.value = '';
                return;
            }

            var normalizedHour = parseInt(hour, 10);

            if (period === 'AM' && normalizedHour === 12) {
                normalizedHour = 0;
            }

            if (period === 'PM' && normalizedHour !== 12) {
                normalizedHour += 12;
            }

            input.value = String(normalizedHour).padStart(2, '0') + ':' + minute;
        }

        document.querySelectorAll('[data-time-picker]').forEach(function (picker) {
            picker.querySelectorAll('select').forEach(function (select) {
                select.addEventListener('change', function () {
                    syncTimePicker(picker);
                });
            });
        });
    </script>
@endsection
