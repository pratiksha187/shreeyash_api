@extends('admin.layouts.app')

@section('title', $project->name . ' | Admin Panel')
@section('headerTitle', 'Project Details')
@section('headerSubtitle', 'Assign work and monitor progress')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $project->name }}</h1>
            <p>{{ $project->site_location ?: 'Project task tracking' }}</p>
        </div>
        <div class="actions" style="margin-top: 0;">
            <a class="btn secondary" href="{{ route('admin.projects.index') }}">Back to Projects</a>
            <a class="btn" href="{{ route('admin.projects.edit', $project) }}">Edit Project</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <section class="detail-grid">
        <div class="card detail-item">
            <span>Status</span>
            <strong>{{ $statuses[$project->status] ?? ucfirst($project->status) }}</strong>
        </div>
        <div class="card detail-item">
            <span>Progress</span>
            <strong>{{ $project->progress_percent }}%</strong>
        </div>
        <div class="card detail-item">
            <span>Planning Manager</span>
            <strong>{{ $project->planningManager?->name ?? '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Target Date</span>
            <strong>{{ $project->target_date?->format('d M Y') ?? '-' }}</strong>
        </div>
    </section>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total Tasks</span>
            <strong>{{ $taskSummary['total'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Pending</span>
            <strong>{{ $taskSummary['pending'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>In Progress</span>
            <strong>{{ $taskSummary['in_progress'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Completed</span>
            <strong>{{ $taskSummary['completed'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Overdue</span>
            <strong>{{ $taskSummary['overdue'] }}</strong>
        </div>
    </section>

    <form class="card form-card" method="POST" action="{{ route('admin.projects.tasks.store', $project) }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Assign New Task</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="title">Task Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required>
                    @error('title') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="assigned_engineer_id">Engineer</label>
                    <select id="assigned_engineer_id" name="assigned_engineer_id">
                        <option value="">Select engineer</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) old('assigned_engineer_id') === (string) $employee->id)>
                                {{ $employee->name }}{{ $employee->designation ? ' - '.$employee->designation : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="assigned_supervisor_id">Supervisor</label>
                    <select id="assigned_supervisor_id" name="assigned_supervisor_id">
                        <option value="">Select supervisor</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) old('assigned_supervisor_id') === (string) $employee->id)>
                                {{ $employee->name }}{{ $employee->designation ? ' - '.$employee->designation : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="work_area">Work Area</label>
                    <input id="work_area" name="work_area" type="text" value="{{ old('work_area') }}" placeholder="Chainage, floor, road section">
                </div>

                <div class="field">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        @foreach ($priorities as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach ($taskStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'pending') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="start_date">Start Date</label>
                    <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}">
                </div>

                <div class="field">
                    <label for="due_date">Due Date</label>
                    <input id="due_date" name="due_date" type="date" value="{{ old('due_date') }}">
                </div>

                <div class="field">
                    <label for="estimated_hours">Estimated Hours</label>
                    <input id="estimated_hours" name="estimated_hours" type="number" min="0" step="0.25" value="{{ old('estimated_hours', 0) }}">
                </div>

                <div class="field full">
                    <label for="description">Task Details</label>
                    <textarea id="description" name="description">{{ old('description') }}</textarea>
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Assign Task</button>
        </div>
    </form>

    <div class="page-header" style="margin-top: 28px;">
        <div>
            <h1>Task Tracker</h1>
            <p>Update status, progress, hours, and completion notes for performance tracking.</p>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Assigned To</th>
                    <th>Dates</th>
                    <th>Progress</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td>
                            <strong>{{ $task->title }}</strong>
                            <div class="table-subtext">{{ $task->work_area ?: '-' }} | {{ $priorities[$task->priority] ?? ucfirst($task->priority) }}</div>
                            <div class="text-wrap">{{ $task->description ?: '-' }}</div>
                        </td>
                        <td>
                            <strong>Engg: {{ $task->engineer?->name ?? '-' }}</strong>
                            <div class="table-subtext">Supervisor: {{ $task->supervisor?->name ?? '-' }}</div>
                        </td>
                        <td>
                            <strong>{{ $task->start_date?->format('d M Y') ?? '-' }}</strong>
                            <div class="table-subtext">Due {{ $task->due_date?->format('d M Y') ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="status-pill status-{{ $task->status }}">{{ $taskStatuses[$task->status] ?? ucfirst($task->status) }}</span>
                            <div class="table-subtext">{{ $task->progress_percent }}% | {{ number_format((float) $task->actual_hours, 2) }} hrs</div>
                        </td>
                        <td>
                            <form class="inline-status-form" method="POST" action="{{ route('admin.projects.tasks.update', [$project, $task]) }}">
                                @csrf
                                @method('PUT')
                                <input name="title" type="text" value="{{ $task->title }}" required>
                                <input name="work_area" type="text" value="{{ $task->work_area }}" placeholder="Work area">
                                <select name="assigned_engineer_id">
                                    <option value="">Select engineer</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected((int) $task->assigned_engineer_id === (int) $employee->id)>{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                                <select name="assigned_supervisor_id">
                                    <option value="">Select supervisor</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected((int) $task->assigned_supervisor_id === (int) $employee->id)>{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                                <select name="priority">
                                    @foreach ($priorities as $value => $label)
                                        <option value="{{ $value }}" @selected($task->priority === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="status">
                                    @foreach ($taskStatuses as $value => $label)
                                        <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="inline-time-fields">
                                    <label>Start
                                        <input name="start_date" type="date" value="{{ $task->start_date?->toDateString() }}">
                                    </label>
                                    <label>Due
                                        <input name="due_date" type="date" value="{{ $task->due_date?->toDateString() }}">
                                    </label>
                                </div>
                                <div class="inline-time-fields">
                                    <label>Est Hrs
                                        <input name="estimated_hours" type="number" min="0" step="0.25" value="{{ $task->estimated_hours }}">
                                    </label>
                                    <label>Act Hrs
                                        <input name="actual_hours" type="number" min="0" step="0.25" value="{{ $task->actual_hours }}">
                                    </label>
                                </div>
                                <input name="progress_percent" type="number" min="0" max="100" value="{{ $task->progress_percent }}">
                                <textarea name="description" placeholder="Task details">{{ $task->description }}</textarea>
                                <textarea name="completion_note" placeholder="Completion note">{{ $task->completion_note }}</textarea>
                                <button class="btn small" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="5">No tasks assigned yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $tasks->links('admin.pagination') }}
    </div>
@endsection
