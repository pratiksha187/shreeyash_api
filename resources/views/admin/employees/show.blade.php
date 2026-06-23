@extends('admin.layouts.app')

@section('title', $employee->name . ' | Admin Panel')
@section('headerTitle', 'Employee Details')
@section('headerSubtitle', 'Calendar attendance view')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $employee->name }}</h1>
            <p>{{ $employee->designation ?? 'Employee' }} attendance for {{ $monthLabel }}.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.employees.index') }}">Back to Employees</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    <section class="detail-grid">
        <div class="card detail-item">
            <span>Email</span>
            <strong>{{ $employee->email }}</strong>
        </div>
        <div class="card detail-item">
            <span>Mobile</span>
            <strong>{{ $employee->mobile ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Join Date</span>
            <strong>{{ $employee->join_date?->format('d M Y') ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Designation</span>
            <strong>{{ $employee->designation ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Gender</span>
            <strong>{{ $employee->gender ? ucfirst($employee->gender) : '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Marital Status</span>
            <strong>{{ $employee->marital_status ? ucfirst($employee->marital_status) : '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Date of Birth</span>
            <strong>{{ $employee->date_of_birth?->format('d M Y') ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Salary</span>
            <strong>{{ $employee->salary ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Confirmation Date</span>
            <strong>{{ $employee->confirmation_date?->format('d M Y') ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Probation Months</span>
            <strong>{{ $employee->probation_months ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Aadhaar Number</span>
            <strong>{{ $employee->aadhaar_number ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Hours Per Day</span>
            <strong>{{ $employee->hours_per_day ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Days Per Week</span>
            <strong>{{ $employee->days_per_week ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Insurance</span>
            <strong>{{ $employee->insurance ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>PT</span>
            <strong>{{ $employee->pt ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Advance</span>
            <strong>{{ $employee->advance ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>PF</span>
            <strong>{{ $employee->pf ?? '-' }}</strong>
        </div>
    </section>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.employees.show', $employee) }}">
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

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.payments.generate') }}">
        @csrf
        <input name="user_id" type="hidden" value="{{ $employee->id }}">
        <section class="form-section">
            <h2 class="section-title">Generate Payment</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="payment_from_date">From Date</label>
                    <input id="payment_from_date" name="from_date" type="date" value="{{ \Carbon\Carbon::parse($selectedMonth . '-01')->startOfMonth()->toDateString() }}" required>
                </div>
                <div class="field">
                    <label for="payment_to_date">To Date</label>
                    <input id="payment_to_date" name="to_date" type="date" value="{{ \Carbon\Carbon::parse($selectedMonth . '-01')->endOfMonth()->toDateString() }}" required>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Calculate Payment</button>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total Records</span>
            <strong>{{ $summary['total_days'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Present</span>
            <strong>{{ $summary['present'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Leave</span>
            <strong>{{ $summary['leave'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Absent</span>
            <strong>{{ $summary['absent'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Half Day</span>
            <strong>{{ $summary['half_day'] }}</strong>
        </div>
    </section>

    <div class="page-header">
        <div>
            <h1>{{ $monthLabel }} Calendar</h1>
            <p>Each date shows the attendance status and leave remarks where available.</p>
        </div>
    </div>

    <div class="card calendar-card">
        <div class="calendar-grid">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                <div class="calendar-head">{{ $dayName }}</div>
            @endforeach

            @foreach ($blankDays as $blankDay)
                <div class="calendar-empty"></div>
            @endforeach

            @foreach ($calendarDays as $day)
                @php
                    $attendance = $day['attendance'];
                    $status = $attendance?->status;
                    $statusLabel = $status ? str_replace('_', ' ', $status) : 'No record';
                    $statusClass = $status ? 'status-' . $status : 'status-empty';
                @endphp
                <div class="calendar-day">
                    <div class="calendar-date">
                        <span>{{ $day['date']->format('j') }}</span>
                        <small>{{ $day['date']->format('D') }}</small>
                    </div>

                    <span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span>

                    @if ($attendance)
                        <div class="calendar-meta">
                            @if ($attendance->check_in_at)
                                In: {{ $attendance->localCheckInAt()?->format('h:i A') }}<br>
                            @endif
                            @if ($attendance->check_out_at)
                                Out: {{ $attendance->localCheckOutAt()?->format('h:i A') }}<br>
                            @endif
                            @if ($attendance->remarks)
                                {{ $attendance->remarks }}
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="page-header">
        <div>
            <h1>{{ $employee->name }} DPRs</h1>
            <p>Daily progress reports submitted by this engineer for {{ $monthLabel }}.</p>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Entries</th>
                    <th>Photos</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dprs as $group)
                    <tr>
                        <td>
                            <strong>{{ \Carbon\Carbon::parse($group->date)->format('d M Y') }}</strong>
                            <div class="table-subtext">DPR for {{ \Carbon\Carbon::parse($group->date)->format('d M Y') }}</div>
                        </td>
                        <td>{{ $group->hours_count }} entries</td>
                        <td>
                            <div class="thumb-row">
                                @foreach ($group->photos->take(4) as $photo)
                                    @if ($photo->publicUrl())
                                        <a href="{{ $photo->publicUrl() }}" target="_blank">
                                            <img class="thumb" src="{{ $photo->publicUrl() }}" alt="DPR photo">
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                            <div class="table-subtext">{{ $group->photos_count }} photos</div>
                        </td>
                        <td>
                            <button class="btn small show-dpr" data-message='@json($group->message)'>Show Message</button>
                            @if ($group->reports->first())
                                <a class="btn small" href="{{ route('admin.dpr-reports.show', $group->reports->first()) }}">View DPR</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="4">No DPR reports found for this employee this month.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- DPR Message Modal -->
    <div id="dpr-modal" style="display:none; position:fixed; inset:0; z-index:1000;">
        <div id="dpr-modal-backdrop" style="position:absolute; inset:0; background:rgba(0,0,0,0.5);"></div>
        <div style="position:relative; max-width:720px; margin:6% auto; background:#fff; border-radius:8px; padding:20px; box-shadow:0 8px 24px rgba(0,0,0,0.2); z-index:1001;">
            <button id="dpr-modal-close" style="position:absolute; right:12px; top:12px; border:0; background:transparent; font-size:20px; cursor:pointer;">&times;</button>
            <h3 style="margin-top:0;">Consolidated DPR</h3>
            <div id="dpr-modal-content" style="white-space:pre-wrap; line-height:1.6; color:#222;"></div>
        </div>
    </div>

    <script>
        (function(){
            function openModal(html){
                var modal = document.getElementById('dpr-modal');
                var content = document.getElementById('dpr-modal-content');
                content.innerHTML = html;
                modal.style.display = 'block';
            }

            function closeModal(){
                var modal = document.getElementById('dpr-modal');
                var content = document.getElementById('dpr-modal-content');
                content.innerHTML = '';
                modal.style.display = 'none';
            }

            document.querySelectorAll('.show-dpr').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var msg = btn.getAttribute('data-message') || '';
                    try{
                        msg = JSON.parse(msg);
                    }catch(e){ /* keep raw */ }
                    // convert newlines to <br> for HTML
                    var html = (''+msg).replace(/\n/g, '<br>');
                    openModal(html);
                });
            });

            document.getElementById('dpr-modal-close').addEventListener('click', closeModal);
            document.getElementById('dpr-modal-backdrop').addEventListener('click', closeModal);
        })();
    </script>
@endsection
