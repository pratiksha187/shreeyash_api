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
        <a class="btn secondary" href="{{ route('admin.employees.index') }}">Back to Employees</a>
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

    <!-- DPR Table with Horizontal Scroll -->
    <div class="card table-wrap" style="overflow-x:auto; margin-bottom:16px;">
        <table style="width:100%; white-space:nowrap;">
            <thead>
                <tr>
                    <th style="width:120px;">Date</th>
                    <th style="width:80px;">Entries</th>
                    <th style="width:120px;">Photos</th>
                    <th style="width:280px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dprs as $group)
                    <tr>
                        <td style="font-weight:500;">
                            {{ \Carbon\Carbon::parse($group->date)->format('d M Y') }}
                        </td>
                        <td>{{ $group->hours_count }}</td>
                        <td>
                            <div class="thumb-row" style="display:flex; gap:4px; flex-wrap:wrap;">
                                @foreach ($group->photos->take(3) as $photo)
                                    <a href="{{ route('admin.dpr-reports.photo', $photo) }}" target="_blank">
                                        <img class="thumb" src="{{ route('admin.dpr-reports.photo', $photo) }}" alt="Photo" style="width:40px; height:40px; object-fit:cover; border-radius:4px;">
                                    </a>
                                @endforeach
                                @if ($group->photos_count > 3)
                                    <span style="display:flex; align-items:center; padding:4px 8px; background:#f0f0f0; border-radius:4px; font-size:11px;">+{{ $group->photos_count - 3 }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <button class="btn small show-dpr" data-message='@json($group->message)' style="margin-right:4px;">Show Message</button>
                            @if ($group->reports->first())
                                <a class="btn small" href="{{ route('admin.dpr-reports.show', $group->reports->first()) }}">View DPR</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="4" style="text-align:center; padding:20px;">No DPR reports found for this month.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
    </section>

    <!-- Compact Calendar -->
    <div style="display:flex; gap:12px; margin-bottom:16px;">
        <div class="card" style="flex:1;">
            <h3 style="margin-top:0; margin-bottom:12px; font-size:14px;">{{ $monthLabel }} Calendar</h3>
            <div class="calendar-grid" style="grid-template-columns:repeat(7, 1fr); gap:4px;">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                    <div style="text-align:center; font-weight:600; font-size:11px; padding:4px;">{{ substr($dayName, 0, 1) }}</div>
                @endforeach

                @foreach ($blankDays as $blankDay)
                    <div></div>
                @endforeach

                @foreach ($calendarDays as $day)
                    @php
                        $attendance = $day['attendance'];
                        $status = $attendance?->status ?? '';
                        $statusClass = $status ? 'status-' . $status : 'status-empty';
                    @endphp
                    <div style="padding:6px; text-align:center; border-radius:4px; background:#f9f9f9; font-size:11px; @if($status) border-left:3px solid currentColor; @endif" class="{{ $statusClass }}">
                        {{ $day['date']->format('d') }}
                    </div>
                @endforeach
            </div>
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
