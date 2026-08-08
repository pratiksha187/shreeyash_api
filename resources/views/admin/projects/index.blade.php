@extends('admin.layouts.app')

@section('title', 'Project Management | Admin Panel')
@section('headerTitle', 'Project Management')
@section('headerSubtitle', 'Plan work, assign tasks, and track employee performance')
@section('bodyClass', 'projects-index-page')

@section('content')
    <style>
        body.projects-index-page .main {
            max-width: 1460px;
            padding: 32px 36px 36px;
        }

        body.projects-index-page .page-header {
            align-items: center;
            margin-bottom: 24px;
        }

        body.projects-index-page .stats-grid {
            gap: 20px;
            margin-bottom: 28px;
        }

        body.projects-index-page .stat-card {
            min-height: 118px;
            padding: 22px 24px;
        }

        body.projects-index-page .report-filter {
            margin-bottom: 28px;
        }

        body.projects-index-page .report-filter .form-grid.three {
            grid-template-columns: minmax(280px, 1fr) minmax(240px, 1fr) 120px;
            align-items: end;
        }

        body.projects-index-page .report-filter .field {
            min-width: 0;
        }

        body.projects-index-page .report-filter .btn {
            width: 100%;
        }

        body.projects-index-page .projects-table {
            table-layout: fixed;
        }

        body.projects-index-page .projects-table th,
        body.projects-index-page .projects-table td {
            vertical-align: middle;
            white-space: normal;
        }

        body.projects-index-page .projects-table th:nth-child(1) { width: 20%; }
        body.projects-index-page .projects-table th:nth-child(2) { width: 22%; }
        body.projects-index-page .projects-table th:nth-child(3) { width: 15%; }
        body.projects-index-page .projects-table th:nth-child(4) { width: 14%; }
        body.projects-index-page .projects-table th:nth-child(5) { width: 10%; }
        body.projects-index-page .projects-table th:nth-child(6) { width: 10%; }
        body.projects-index-page .projects-table th:nth-child(7) { width: 260px; }

        body.projects-index-page .projects-table .table-actions {
            justify-content: flex-start;
            min-width: 0;
        }

        body.projects-index-page .projects-table .btn.small {
            min-width: 64px;
        }

        body.projects-index-page .section-spacer {
            margin-top: 34px;
        }

        .pm-dashboard-title {
            margin: 0 0 22px;
            color: #1f2937;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 0.24em;
            text-align: center;
            text-transform: uppercase;
        }

        .pm-shortcuts {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
            padding: 16px;
            border-left: 4px solid var(--primary);
        }

        .pm-shortcut {
            display: grid;
            gap: 8px;
            justify-items: center;
            min-height: 92px;
            padding: 10px 8px;
            border: 1px solid #f7c99d;
            border-radius: 8px;
            background: #fff;
            color: var(--brand-blue-dark);
            text-align: center;
            text-decoration: none;
        }

        .pm-shortcut.is-disabled {
            opacity: 0.58;
            cursor: not-allowed;
        }

        .pm-shortcut-icon {
            display: inline-grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border: 2px solid #f5a436;
            border-radius: 50%;
            color: var(--primary);
        }

        .pm-shortcut-icon svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        .pm-shortcut span:last-child {
            font-size: 12px;
            font-weight: 900;
            line-height: 1.25;
        }

        .pm-dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.9fr);
            gap: 18px;
            margin-bottom: 18px;
        }

        .pm-panel {
            overflow: hidden;
            border-left: 4px solid var(--primary);
        }

        .pm-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
        }

        .pm-panel-head h2 {
            margin: 0;
            font-size: 16px;
        }

        .pm-panel-chip {
            padding: 5px 10px;
            border-radius: 999px;
            background: #e5edf3;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }

        .pm-todo-table th,
        .pm-todo-table td {
            padding: 10px 14px;
            text-align: center;
        }

        .pm-todo-table th:first-child,
        .pm-todo-table td:first-child {
            text-align: left;
        }

        .pm-count {
            display: inline-flex;
            min-width: 26px;
            height: 22px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            color: #fff;
            font-size: 12px;
            font-weight: 900;
        }

        .pm-count.warning { background: #f59e0b; }
        .pm-count.success { background: #10b981; }
        .pm-count.danger { background: #ef4444; }

        .pm-search-box {
            display: grid;
            min-height: 242px;
            place-items: center;
            padding: 22px;
            color: var(--muted);
            text-align: center;
        }

        .pm-search-box svg {
            width: 52px;
            height: 52px;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .pm-alert-card {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) 180px minmax(220px, 1fr) 58px;
            gap: 14px;
            align-items: end;
            margin-bottom: 28px;
            padding: 16px;
            border-left: 4px solid var(--primary);
        }

        @media (max-width: 900px) {
            body.projects-index-page .main {
                padding: 24px 18px;
            }

            body.projects-index-page .report-filter .form-grid.three {
                grid-template-columns: 1fr;
            }

            .pm-shortcuts {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .pm-dashboard-grid,
            .pm-alert-card {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $shortcutIcon = ['M4 6h16', 'M4 12h16', 'M4 18h10'];
        $shortcuts = [
            ['label' => 'Project Manager', 'route' => route('admin.projects.index'), 'icon' => ['M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8', 'M4 21a8 8 0 0 1 16 0']],
            ['label' => 'Group / Task List', 'route' => route('admin.projects.index'), 'icon' => ['M4 7h16', 'M4 12h16', 'M4 17h10']],
            ['label' => 'Work Indent List', 'route' => null, 'icon' => ['M5 7h14', 'M5 12h14', 'M5 17h8']],
            ['label' => 'Work Order List', 'route' => route('admin.projects.index'), 'icon' => ['M7 3h10v18H7z', 'M10 7h4', 'M10 11h4', 'M10 15h3']],
            ['label' => 'RFI List', 'route' => null, 'icon' => ['M7 3h10v18H7z', 'M10 7h4', 'M10 11h4', 'M10 15h2']],
            ['label' => 'Daily Progress List', 'route' => route('admin.dpr-reports.index'), 'icon' => ['M4 19h16', 'M6 19V9l6-4 6 4v10']],
            ['label' => 'RA Bill List', 'route' => null, 'icon' => ['M6 3h12v18H6z', 'M9 8h6', 'M9 12h6', 'M9 16h3']],
            ['label' => 'Sub Contractor List', 'route' => route('admin.contractors.index'), 'icon' => ['M6 20v-2a6 6 0 0 1 12 0v2', 'M9 10a3 3 0 1 0 6 0 3 3 0 0 0-6 0']],
            ['label' => 'Pour Card List', 'route' => null, 'icon' => ['M5 20h14', 'M8 20V8l4-4 4 4v12', 'M10 12h4']],
            ['label' => 'Chainage List', 'route' => null, 'icon' => ['M4 12h16', 'M7 9v6', 'M12 9v6', 'M17 9v6']],
            ['label' => 'Material Work Report', 'route' => route('admin.material-stock.index'), 'icon' => ['M21 8l-9-5-9 5 9 5 9-5z', 'M3 8v8l9 5 9-5V8', 'M12 13v8']],
            ['label' => 'Project Manager', 'route' => route('admin.projects.create'), 'icon' => ['M12 5v14', 'M5 12h14']],
        ];
    @endphp

    <h1 class="pm-dashboard-title">Dashboard Project-Management</h1>

    @include('admin.projects.partials.workflow')

    <section class="card pm-shortcuts">
        @foreach ($shortcuts as $shortcut)
            @if ($shortcut['route'])
                <a class="pm-shortcut" href="{{ $shortcut['route'] }}">
            @else
                <span class="pm-shortcut is-disabled" title="Module not added yet">
            @endif
                <span class="pm-shortcut-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        @foreach ($shortcut['icon'] as $path)
                            <path d="{{ $path }}"></path>
                        @endforeach
                    </svg>
                </span>
                <span>{{ $shortcut['label'] }}</span>
            @if ($shortcut['route'])
                </a>
            @else
                </span>
            @endif
        @endforeach
    </section>

    <section class="pm-dashboard-grid">
        <div class="card pm-panel">
            <div class="pm-panel-head">
                <h2>To Do List</h2>
                <span class="pm-panel-chip">As Per Current and Previous Financial Year</span>
            </div>
            <table class="pm-todo-table">
                <thead>
                    <tr>
                        <th>Working Log</th>
                        <th>Pending To Approval</th>
                        <th>Pending To Next Process</th>
                        <th>Rejected</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($todoRows as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td><span class="pm-count warning">{{ $row['approval'] }}</span></td>
                            <td><span class="pm-count success">{{ $row['next'] }}</span></td>
                            <td><span class="pm-count danger">{{ $row['rejected'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card pm-panel">
            <div class="pm-panel-head">
                <h2>Quick Search (Task Detail)</h2>
                <span class="pm-panel-chip">-</span>
            </div>
            <div class="pm-search-box">
                <div>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 21l-4.35-4.35"></path>
                        <path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14"></path>
                    </svg>
                    <strong>Use the filters below to search project/task detail.</strong>
                </div>
            </div>
        </div>
    </section>

    <form class="card pm-alert-card" method="GET" action="{{ route('admin.projects.index') }}">
        <div>
            <label for="alert_date">Alert</label>
            <input id="alert_date" name="alert_date" type="date" value="{{ now()->toDateString() }}">
        </div>
        <div>
            <label for="alert_status">Status</label>
            <select id="alert_status" name="status">
                <option value="">All</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="alert_search">Task / Project Search</label>
            <input id="alert_search" name="search" type="search" value="{{ $search }}" placeholder="Project, code, client, location">
        </div>
        <button class="btn" type="submit">Go</button>
    </form>

    <div class="page-header">
        <div>
            <h1>Project Management List</h1>
            <p>Track project progress, pending work, overdue tasks, and engineer performance.</p>
        </div>
        <a class="btn" href="{{ route('admin.projects.create') }}">Add Project</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total Projects</span>
            <strong>{{ $summary['total'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Active Projects</span>
            <strong>{{ $summary['active'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Open Tasks</span>
            <strong>{{ $summary['tasks_pending'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Overdue Tasks</span>
            <strong>{{ $summary['tasks_overdue'] }}</strong>
        </div>
    </section>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.projects.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filter Projects</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="search">Search</label>
                    <input id="search" name="search" type="search" value="{{ old('search', $search) }}" placeholder="Project, code, client, location">
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Filter</button>
                </div>
            </div>
        </section>
    </form>

    <div class="card table-wrap">
        <table class="projects-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Planning Manager</th>
                    <th>Timeline</th>
                    <th>Progress</th>
                    <th>Tasks</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $project)
                    <tr>
                        <td>
                            <a class="table-link" href="{{ route('admin.projects.show', $project) }}">{{ $project->name }}</a>
                            <div class="table-subtext">{{ $project->code ?: 'No code' }}{{ $project->site_location ? ' | '.$project->site_location : '' }}</div>
                        </td>
                        <td>{{ $project->planningManager?->name ?? '-' }}</td>
                        <td>
                            <strong>{{ $project->start_date?->format('d M Y') ?? '-' }}</strong>
                            <div class="table-subtext">Target {{ $project->target_date?->format('d M Y') ?? '-' }}</div>
                        </td>
                        <td>
                            <strong>{{ $project->progress_percent }}%</strong>
                            <div class="table-subtext">Budget {{ number_format((float) $project->budget_amount, 2) }}</div>
                        </td>
                        <td>
                            <strong>{{ $project->completed_tasks_count }}/{{ $project->tasks_count }}</strong>
                            <div class="table-subtext">{{ $project->overdue_tasks_count }} overdue</div>
                        </td>
                        <td><span class="status-pill status-{{ $project->status }}">{{ $statuses[$project->status] ?? ucfirst($project->status) }}</span></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn small" href="{{ route('admin.projects.show', $project) }}">Open</a>
                                <a class="btn small secondary" href="{{ route('admin.projects.structure', $project) }}">Structure</a>
                                <a class="btn small secondary" href="{{ route('admin.projects.boq', $project) }}">BOQ</a>
                                <a class="btn small secondary" href="{{ route('admin.projects.edit', $project) }}">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No projects added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $projects->links('admin.pagination') }}
    </div>

    <div class="page-header section-spacer">
        <div>
            <h1>Employee Performance</h1>
            <p>Completed project work by assigned engineer.</p>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Designation</th>
                    <th>Assigned Tasks</th>
                    <th>Completed Tasks</th>
                    <th>Actual Hours</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($performance as $employee)
                    <tr>
                        <td>
                            <a class="table-link" href="{{ route('admin.employees.show', $employee->id) }}">{{ $employee->name }}</a>
                        </td>
                        <td>{{ $employee->designation ?? '-' }}</td>
                        <td>{{ $employee->assigned_tasks_count }}</td>
                        <td>{{ $employee->completed_tasks_count }}</td>
                        <td>{{ number_format((float) $employee->completed_hours, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="5">No task performance data yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
