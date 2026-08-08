@extends('admin.layouts.app')

@section('title', $employee->name . ' | Admin Panel')
@section('headerTitle', 'Employee Details')
@section('headerSubtitle', 'DPRs and attendance view')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $employee->name }}</h1>
            <p>{{ $employee->designation ?? 'Employee' }} · {{ $employee->mobile ?? '-' }}</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('admin.employees.edit', $employee) }}">Edit Employee</a>
            <a class="btn secondary" href="{{ route('admin.employees.index') }}">Back to Employees</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    <!-- Compact Employee Info -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:8px; margin-bottom:16px;">
        <div class="card detail-item" style="padding:12px 16px;">
            <span style="font-size:12px;">Email</span>
            <strong style="font-size:13px;">{{ $employee->email }}</strong>
        </div>
        <div class="card detail-item" style="padding:12px 16px;">
            <span style="font-size:12px;">Join Date</span>
            <strong style="font-size:13px;">{{ $employee->join_date?->format('d M Y') ?? '-' }}</strong>
        </div>
        <div class="card detail-item" style="padding:12px 16px;">
            <span style="font-size:12px;">Salary</span>
            <strong style="font-size:13px;">{{ $employee->salary ?? '-' }}</strong>
        </div>
        <div class="card detail-item" style="padding:12px 16px;">
            <span style="font-size:12px;">Hours/Day</span>
            <strong style="font-size:13px;">{{ $employee->hours_per_day ?? '-' }}</strong>
        </div>
    </div>

    <!-- DPR Section with Month Filter -->
    <form class="card form-card report-filter" method="GET" action="{{ route('admin.employees.show', $employee) }}" style="margin-bottom:16px;">
        <section class="form-section" style="margin-bottom:0;">
            <h2 class="section-title" style="margin-top:0; margin-bottom:8px;">Daily Progress Reports</h2>
            <div class="form-grid three" style="gap:12px;">
                <div class="field">
                    <label for="month">Month</label>
                    <input id="month" name="month" type="month" value="{{ old('month', $selectedMonth) }}">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit" style="width:100%;">Apply Filter</button>
                </div>
            </div>
        </section>
    </form>

    <!-- DPR Table - Clean Layout -->
    <div class="card table-wrap" style="margin-bottom:24px;">
        <table style="width:100%;">
            <thead>
                <tr>
                    <th>DATE</th>
                    <th>ENTRIES</th>
                    <th>PHOTOS</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dprs as $group)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($group->date)->format('d/m/Y') }}</td>
                        <td>{{ $group->hours_count }}</td>
                        <td>
                            <div style="display:flex; gap:6px; align-items:center;">
                                @foreach ($group->photos->take(2) as $photo)
                                    <a href="{{ route('admin.dpr-reports.photo', $photo) }}" target="_blank" style="display:inline-block;">
                                        <img src="{{ route('admin.dpr-reports.photo', $photo) }}" alt="Photo" style="width:32px; height:32px; object-fit:cover; border-radius:3px;">
                                    </a>
                                @endforeach
                                @if ($group->photos_count > 2)
                                    <span style="font-size:12px; color:#666;">+{{ $group->photos_count - 2 }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <button class="btn small show-dpr" data-message='@json($group->message)' style="margin-right:6px; background:#007bff; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-size:12px;">Show Message</button>
                            @if ($group->reports->first())
                                <a class="btn small" href="{{ route('admin.dpr-reports.show', $group->reports->first()) }}" style="background:#f0f0f0; padding:6px 12px; border-radius:4px; text-decoration:none; color:#333; font-size:12px;">View DPR</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; padding:24px; color:#999;">No DPR reports found for this month.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $dprs->links('admin.pagination') }}
    </div>

    <!-- Attendance Stats -->
    <section class="stats-grid" style="margin-bottom:16px;">
        <div class="card stat-card">
            <span>Total Days</span>
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
        <div class="card stat-card">
            <span>Paid Holidays</span>
            <strong>{{ $summary['paid_holidays'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Total OT</span>
            <strong>{{ $summary['overtime'] }}</strong>
        </div>
    </section>

    <!-- Attendance Calendar -->
    <div class="card calendar-card employee-calendar-card" style="margin-bottom:16px;">
        <div class="employee-calendar-title">
            <h3>{{ $monthLabel }} Calendar</h3>
            <div class="employee-calendar-legend">
                <span><i class="legend-dot present"></i>Present</span>
                <span><i class="legend-dot completed-hours"></i>Completed Hours</span>
                <span><i class="legend-dot late-short"></i>Late / Short</span>
                <span><i class="legend-dot leave"></i>Leave</span>
                <span><i class="legend-dot absent"></i>Absent</span>
                <span><i class="legend-dot half-day"></i>Half Day</span>
                <span><i class="legend-dot holiday"></i>Paid Holiday</span>
                <span><i class="legend-dot sunday"></i>Sunday</span>
            </div>
        </div>

        <div class="calendar-grid employee-calendar-grid">
            @foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $dayName)
                <div class="calendar-head @if($loop->first) calendar-sunday-head @endif">{{ $dayName }}</div>
            @endforeach

            @foreach ($blankDays as $blankDay)
                <div class="calendar-empty"></div>
            @endforeach

            @foreach ($calendarDays as $day)
                @php
                    $attendance = $day['attendance'];
                    $attendanceMeta = $day['attendanceMeta'];
                    $holiday = $day['holiday'];
                    $status = $attendance?->status ?? '';
                    $isSunday = $day['date']->isSunday();
                    $statusLabel = $attendanceMeta['label'] ?? ($holiday ? 'Paid holiday' : ($isSunday ? 'Sunday' : 'No record'));
                    $statusClass = $attendance ? $attendanceMeta['class'] : ($holiday ? 'status-holiday' : ($isSunday ? 'status-sunday' : 'status-empty'));
                @endphp
                <div class="calendar-day @if($isSunday) calendar-sunday @endif @if($holiday) calendar-holiday @endif">
                    <div class="calendar-date">
                        <span>{{ $day['date']->format('d') }}</span>
                        <small>{{ $day['date']->format('D') }}</small>
                    </div>
                    <span class="status-pill {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                    @if ($attendance)
                        <div class="calendar-time-grid">
                            <span>In <strong>{{ $attendanceMeta['check_in'] ?? '-' }}</strong></span>
                            <span>Out <strong>{{ $attendanceMeta['check_out'] ?? '-' }}</strong></span>
                            <span>Hours <strong>{{ $attendanceMeta['worked'] ?? '-' }}</strong></span>
                            <span class="calendar-ot-row">OT <strong>{{ $attendanceMeta['ot'] ?? '-' }}</strong></span>
                        </div>
                        @if ($attendanceMeta['note'])
                            <div class="calendar-time-note">{{ $attendanceMeta['note'] }}</div>
                        @endif
                    @endif
                    @if ($holiday)
                        <div class="calendar-note">{{ $holiday['name'] }}</div>
                    @endif
                </div>
            @endforeach

            @foreach ($trailingBlankDays as $blankDay)
                <div class="calendar-empty"></div>
            @endforeach
        </div>
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
