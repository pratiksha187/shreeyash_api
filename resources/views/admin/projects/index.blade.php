@extends('admin.layouts.app')

@section('title', 'Project Management | Admin Panel')
@section('headerTitle', 'Project Management')
@section('headerSubtitle', 'Plan work, assign tasks, and track employee performance')

@section('content')
    <div class="page-header">
        <div>
            <h1>Project Management</h1>
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
        <table>
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

    <div class="page-header" style="margin-top: 28px;">
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
