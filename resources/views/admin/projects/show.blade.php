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
        <div class="actions">
            <a class="btn secondary" href="{{ route('admin.projects.index') }}">Back to Projects</a>
            <a class="btn secondary" href="{{ route('admin.projects.structure', $project) }}">Structure</a>
            <a class="btn secondary" href="{{ route('admin.projects.boq', $project) }}">BOQ List</a>
            <a class="btn" href="{{ route('admin.projects.edit', $project) }}">Edit Project</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @include('admin.projects.partials.workflow', ['project' => $project])

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
        <div class="card detail-item">
            <span>Work Order</span>
            <strong>{{ $project->work_order_number ?: '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>BOQ Reference</span>
            <strong>{{ $project->boq_reference ?: '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>SOR Reference</span>
            <strong>{{ $project->sor_reference ?: '-' }}</strong>
        </div>
        <div class="card detail-item">
            <span>WO Date</span>
            <strong>{{ $project->work_order_date?->format('d M Y') ?? '-' }}</strong>
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

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Planned Qty</span>
            <strong>{{ number_format($costSummary['planned_quantity'], 3) }} {{ $project->quantity_unit }}</strong>
        </div>
        <div class="card stat-card">
            <span>Executed Qty</span>
            <strong>{{ number_format($costSummary['executed_quantity'], 3) }} {{ $project->quantity_unit }}</strong>
        </div>
        <div class="card stat-card">
            <span>Balance Qty</span>
            <strong>{{ number_format($costSummary['quantity_balance'], 3) }} {{ $project->quantity_unit }}</strong>
        </div>
        <div class="card stat-card">
            <span>Planned Cost</span>
            <strong>{{ number_format($costSummary['planned_cost'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Actual Cost</span>
            <strong>{{ number_format($costSummary['actual_cost'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Cost To Complete</span>
            <strong>{{ number_format($costSummary['cost_to_complete'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Budget Variance</span>
            <strong>{{ number_format($costSummary['budget_variance'], 2) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Material Baseline</span>
            <strong>{{ number_format($costSummary['planned_material_qty'], 3) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Labour Baseline</span>
            <strong>{{ $costSummary['planned_labour_count'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Machinery Baseline</span>
            <strong>{{ $costSummary['planned_machinery_count'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Variance Alerts</span>
            <strong>{{ $costSummary['variance_alerts'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Stock Opening</span>
            <strong>{{ number_format($costSummary['opening_stock_qty'], 3) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Stock Receipt</span>
            <strong>{{ number_format($costSummary['receipt_qty'], 3) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Issue / Consumption</span>
            <strong>{{ number_format($costSummary['issue_consumption_qty'], 3) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Stock Return</span>
            <strong>{{ number_format($costSummary['return_qty'], 3) }}</strong>
        </div>
        <div class="card stat-card">
            <span>Closing Stock</span>
            <strong>{{ number_format($costSummary['closing_stock_qty'], 3) }}</strong>
        </div>
    </section>

    <form id="project-planning" class="card form-card" method="POST" action="{{ route('admin.projects.tasks.store', $project) }}">
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
                    <label for="boq_item_number">BOQ Item</label>
                    <select id="boq_item_number" name="boq_item_number">
                        <option value="">Select BOQ item</option>
                        @foreach ($boqItems as $boqItem)
                            <option
                                value="{{ $boqItem->boq_no }}"
                                data-unit="{{ $boqItem->unit }}"
                                data-qty="{{ (float) $boqItem->scope_qty }}"
                                data-rate="{{ (float) $boqItem->rate }}"
                                @selected(old('boq_item_number') === $boqItem->boq_no)
                            >
                                {{ $boqItem->boq_no }} - {{ $boqItem->task_name }}{{ $boqItem->unit ? ' | '.$boqItem->unit : '' }}{{ (float) $boqItem->rate > 0 ? ' | Rate '.number_format((float) $boqItem->rate, 2) : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="sor_item_number">SOR Item</label>
                    <input id="sor_item_number" name="sor_item_number" type="text" value="{{ old('sor_item_number') }}" placeholder="SOR / rate item">
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

                <div class="field">
                    <label for="quantity_unit">Unit</label>
                    <input id="quantity_unit" name="quantity_unit" type="text" value="{{ old('quantity_unit', $project->quantity_unit) }}" placeholder="Cum, MT, Sqm">
                </div>

                <div class="field">
                    <label for="planned_material_qty">Material Baseline Qty</label>
                    <input id="planned_material_qty" name="planned_material_qty" type="number" min="0" step="0.001" value="{{ old('planned_material_qty', 0) }}">
                </div>

                <div class="field">
                    <label for="material_id">Material</label>
                    <select id="material_id" name="material_id">
                        <option value="">Select material</option>
                        @foreach ($materials as $material)
                            <option
                                value="{{ $material->id }}"
                                data-unit="{{ $material->unit }}"
                                @selected((string) old('material_id') === (string) $material->id)
                            >
                                {{ $material->name }}{{ $material->unit ? ' | '.$material->unit : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="planned_labour_count">Labour Baseline</label>
                    <input id="planned_labour_count" name="planned_labour_count" type="number" min="0" step="1" value="{{ old('planned_labour_count', 0) }}">
                </div>

                <div class="field">
                    <label for="planned_machinery_count">Machinery Baseline</label>
                    <input id="planned_machinery_count" name="planned_machinery_count" type="number" min="0" step="1" value="{{ old('planned_machinery_count', 0) }}">
                </div>

                <div class="field">
                    <label for="planned_quantity">Planned Quantity</label>
                    <input id="planned_quantity" name="planned_quantity" type="number" min="0" step="0.001" value="{{ old('planned_quantity', 0) }}">
                </div>

                <div class="field">
                    <label for="executed_quantity">Executed Quantity</label>
                    <input id="executed_quantity" name="executed_quantity" type="number" min="0" step="0.001" value="{{ old('executed_quantity', 0) }}">
                </div>

                <div class="field">
                    <label for="rate">SOR Rate</label>
                    <input id="rate" name="rate" type="number" min="0" step="0.01" value="{{ old('rate', 0) }}">
                </div>

                <div class="field">
                    <label for="planned_cost">Planned Cost</label>
                    <input id="planned_cost" name="planned_cost" type="number" min="0" step="0.01" value="{{ old('planned_cost', 0) }}" placeholder="auto from qty x rate if empty">
                </div>

                <div class="field">
                    <label for="actual_cost">Actual Cost</label>
                    <input id="actual_cost" name="actual_cost" type="number" min="0" step="0.01" value="{{ old('actual_cost', 0) }}">
                </div>

                <div class="field">
                    <label for="variance_limit_percent">Variance Alert %</label>
                    <input id="variance_limit_percent" name="variance_limit_percent" type="number" min="0" max="100" step="0.01" value="{{ old('variance_limit_percent', 10) }}">
                </div>

                <div class="field">
                    <label for="opening_stock_qty">Opening Stock</label>
                    <input id="opening_stock_qty" name="opening_stock_qty" type="number" min="0" step="0.001" value="{{ old('opening_stock_qty', 0) }}">
                </div>

                <div class="field">
                    <label for="receipt_qty">Receipt Qty</label>
                    <input id="receipt_qty" name="receipt_qty" type="number" min="0" step="0.001" value="{{ old('receipt_qty', 0) }}">
                </div>

                <div class="field">
                    <label for="issue_consumption_qty">Issue / Consumption</label>
                    <input id="issue_consumption_qty" name="issue_consumption_qty" type="number" min="0" step="0.001" value="{{ old('issue_consumption_qty', 0) }}">
                </div>

                <div class="field">
                    <label for="return_qty">Return Qty</label>
                    <input id="return_qty" name="return_qty" type="number" min="0" step="0.001" value="{{ old('return_qty', 0) }}">
                </div>

                <div class="field">
                    <label for="closing_stock_qty">Closing Stock</label>
                    <input id="closing_stock_qty" name="closing_stock_qty" type="number" min="0" step="0.001" value="{{ old('closing_stock_qty', 0) }}" readonly>
                </div>

                <div class="field full">
                    <label for="material_template">Material Template</label>
                    <textarea id="material_template" name="material_template" placeholder="Example: Cement 10 bags, sand 2 brass, aggregate 3 brass">{{ old('material_template') }}</textarea>
                </div>

                <div class="field full">
                    <label for="description">Detailed Estimation / Task Details</label>
                    <textarea id="description" name="description">{{ old('description') }}</textarea>
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Assign Task</button>
        </div>
    </form>

    <div class="page-header section-spacer">
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
                            <div class="table-subtext">{{ $task->work_area ?: '-' }} | BOQ {{ $task->boq_item_number ?: '-' }} | SOR {{ $task->sor_item_number ?: '-' }}</div>
                            <div class="table-subtext">{{ $priorities[$task->priority] ?? ucfirst($task->priority) }}</div>
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
                            <div class="table-subtext">Qty {{ number_format((float) $task->executed_quantity, 3) }}/{{ number_format((float) $task->planned_quantity, 3) }} {{ $task->quantity_unit }}</div>
                            <div class="table-subtext">Cost {{ number_format((float) $task->actual_cost, 2) }}/{{ number_format((float) $task->planned_cost, 2) }}</div>
                            <div class="table-subtext">Material {{ $task->material?->name ?? '-' }} | Plan {{ number_format((float) $task->planned_material_qty, 3) }}</div>
                            <div class="table-subtext">Stock {{ number_format((float) $task->opening_stock_qty, 3) }} + {{ number_format((float) $task->receipt_qty, 3) }} - {{ number_format((float) $task->issue_consumption_qty, 3) }} - {{ number_format((float) $task->return_qty, 3) }} = {{ number_format((float) $task->closing_stock_qty, 3) }}</div>
                            <div class="table-subtext">Labour {{ $task->planned_labour_count }} | Machinery {{ $task->planned_machinery_count }}</div>
                            <div class="table-subtext">Variance limit {{ number_format((float) $task->variance_limit_percent, 2) }}%</div>
                        </td>
                        <td>
                            <form class="inline-status-form" method="POST" action="{{ route('admin.projects.tasks.update', [$project, $task]) }}">
                                @csrf
                                @method('PUT')
                                <input name="title" type="text" value="{{ $task->title }}" required>
                                <input name="work_area" type="text" value="{{ $task->work_area }}" placeholder="Work area">
                                <select name="boq_item_number">
                                    <option value="">Select BOQ item</option>
                                    @foreach ($boqItems as $boqItem)
                                        <option value="{{ $boqItem->boq_no }}" @selected($task->boq_item_number === $boqItem->boq_no)>
                                            {{ $boqItem->boq_no }} - {{ $boqItem->task_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input name="sor_item_number" type="text" value="{{ $task->sor_item_number }}" placeholder="SOR item">
                                <select name="material_id">
                                    <option value="">Select material</option>
                                    @foreach ($materials as $material)
                                        <option value="{{ $material->id }}" @selected((int) $task->material_id === (int) $material->id)>
                                            {{ $material->name }}{{ $material->unit ? ' | '.$material->unit : '' }}
                                        </option>
                                    @endforeach
                                </select>
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
                                <div class="inline-time-fields">
                                    <label>Unit
                                        <input name="quantity_unit" type="text" value="{{ $task->quantity_unit }}">
                                    </label>
                                    <label>Rate
                                        <input name="rate" type="number" min="0" step="0.01" value="{{ $task->rate }}">
                                    </label>
                                </div>
                                <div class="inline-time-fields">
                                    <label>Mat Qty
                                        <input name="planned_material_qty" type="number" min="0" step="0.001" value="{{ $task->planned_material_qty }}">
                                    </label>
                                    <label>Labour
                                        <input name="planned_labour_count" type="number" min="0" step="1" value="{{ $task->planned_labour_count }}">
                                    </label>
                                </div>
                                <div class="inline-time-fields">
                                    <label>Opening
                                        <input name="opening_stock_qty" type="number" min="0" step="0.001" value="{{ $task->opening_stock_qty }}">
                                    </label>
                                    <label>Receipt
                                        <input name="receipt_qty" type="number" min="0" step="0.001" value="{{ $task->receipt_qty }}">
                                    </label>
                                </div>
                                <div class="inline-time-fields">
                                    <label>Issue
                                        <input name="issue_consumption_qty" type="number" min="0" step="0.001" value="{{ $task->issue_consumption_qty }}">
                                    </label>
                                    <label>Return
                                        <input name="return_qty" type="number" min="0" step="0.001" value="{{ $task->return_qty }}">
                                    </label>
                                </div>
                                <div class="inline-time-fields">
                                    <label>Machinery
                                        <input name="planned_machinery_count" type="number" min="0" step="1" value="{{ $task->planned_machinery_count }}">
                                    </label>
                                    <label>Alert %
                                        <input name="variance_limit_percent" type="number" min="0" max="100" step="0.01" value="{{ $task->variance_limit_percent }}">
                                    </label>
                                </div>
                                <div class="inline-time-fields">
                                    <label>Plan Qty
                                        <input name="planned_quantity" type="number" min="0" step="0.001" value="{{ $task->planned_quantity }}">
                                    </label>
                                    <label>Exec Qty
                                        <input name="executed_quantity" type="number" min="0" step="0.001" value="{{ $task->executed_quantity }}">
                                    </label>
                                </div>
                                <div class="inline-time-fields">
                                    <label>Plan Cost
                                        <input name="planned_cost" type="number" min="0" step="0.01" value="{{ $task->planned_cost }}">
                                    </label>
                                    <label>Act Cost
                                        <input name="actual_cost" type="number" min="0" step="0.01" value="{{ $task->actual_cost }}">
                                    </label>
                                </div>
                                <input name="progress_percent" type="number" min="0" max="100" value="{{ $task->progress_percent }}">
                                <textarea name="material_template" placeholder="Material template">{{ $task->material_template }}</textarea>
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

    <script>
        var boqSelect = document.getElementById('boq_item_number');

        if (boqSelect) {
            boqSelect.addEventListener('change', function () {
                var option = boqSelect.options[boqSelect.selectedIndex];
                var unit = option.getAttribute('data-unit') || '';
                var qty = option.getAttribute('data-qty') || '';
                var rate = option.getAttribute('data-rate') || '';

                if (unit && document.getElementById('quantity_unit')) {
                    document.getElementById('quantity_unit').value = unit;
                }

                if (qty && document.getElementById('planned_quantity')) {
                    document.getElementById('planned_quantity').value = qty;
                }

                if (rate && document.getElementById('rate')) {
                    document.getElementById('rate').value = rate;
                }
            });
        }

        var materialSelect = document.getElementById('material_id');

        if (materialSelect) {
            materialSelect.addEventListener('change', function () {
                var option = materialSelect.options[materialSelect.selectedIndex];
                var unit = option.getAttribute('data-unit') || '';

                if (unit && document.getElementById('quantity_unit')) {
                    document.getElementById('quantity_unit').value = unit;
                }
            });
        }

        function updateClosingStock() {
            var opening = parseFloat(document.getElementById('opening_stock_qty')?.value || '0');
            var receipt = parseFloat(document.getElementById('receipt_qty')?.value || '0');
            var issue = parseFloat(document.getElementById('issue_consumption_qty')?.value || '0');
            var returnQty = parseFloat(document.getElementById('return_qty')?.value || '0');
            var closing = document.getElementById('closing_stock_qty');

            if (closing) {
                closing.value = Math.max((opening + receipt) - issue - returnQty, 0).toFixed(3);
            }
        }

        ['opening_stock_qty', 'receipt_qty', 'issue_consumption_qty', 'return_qty'].forEach(function (id) {
            var input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', updateClosingStock);
            }
        });

        updateClosingStock();
    </script>
@endsection
