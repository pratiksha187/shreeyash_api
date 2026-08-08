<section class="form-section">
    <h2 class="section-title">Project Details</h2>
    <div class="form-grid">
        <div class="field">
            <label for="name">Project Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $project->name ?? '') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="code">Project Code</label>
            <input id="code" name="code" type="text" value="{{ old('code', $project->code ?? '') }}" placeholder="Optional">
            @error('code') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="client_name">Client Name</label>
            <input id="client_name" name="client_name" type="text" value="{{ old('client_name', $project->client_name ?? '') }}">
            @error('client_name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="site_location">Site / Location</label>
            @if ($sites->isNotEmpty())
                <select id="site_location" name="site_location">
                    <option value="">Select site</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->name }}" @selected(old('site_location', $project->site_location ?? '') === $site->name)>
                            {{ $site->name }}{{ $site->address ? ' - '.$site->address : '' }}
                        </option>
                    @endforeach
                </select>
            @else
                <input id="site_location" name="site_location" type="text" value="{{ old('site_location', $project->site_location ?? '') }}" placeholder="Add sites in Site Master to show dropdown">
            @endif
            @error('site_location') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="planning_manager_id">Planning Manager</label>
            <select id="planning_manager_id" name="planning_manager_id">
                <option value="">Select employee</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((string) old('planning_manager_id', $project->planning_manager_id ?? '') === (string) $employee->id)>
                        {{ $employee->name }}{{ $employee->designation ? ' - '.$employee->designation : '' }}
                    </option>
                @endforeach
            </select>
            @error('planning_manager_id') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="work_order_number">Work Order Number</label>
            <input id="work_order_number" name="work_order_number" type="text" value="{{ old('work_order_number', $project->work_order_number ?? '') }}" placeholder="WO / agreement no">
            @error('work_order_number') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="work_order_date">Work Order Date</label>
            <input id="work_order_date" name="work_order_date" type="date" value="{{ old('work_order_date', isset($project) ? $project->work_order_date?->toDateString() : '') }}">
            @error('work_order_date') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="boq_reference">BOQ Reference</label>
            <input id="boq_reference" name="boq_reference" type="text" value="{{ old('boq_reference', $project->boq_reference ?? '') }}" placeholder="BOQ file / revision">
            @error('boq_reference') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="sor_reference">SOR / Rate Analysis Reference</label>
            <input id="sor_reference" name="sor_reference" type="text" value="{{ old('sor_reference', $project->sor_reference ?? '') }}" placeholder="SOR year / rate sheet">
            @error('sor_reference') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $project->status ?? 'planned') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="start_date">Start Date</label>
            <input id="start_date" name="start_date" type="date" value="{{ old('start_date', isset($project) ? $project->start_date?->toDateString() : '') }}">
            @error('start_date') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="target_date">Target Date</label>
            <input id="target_date" name="target_date" type="date" value="{{ old('target_date', isset($project) ? $project->target_date?->toDateString() : '') }}">
            @error('target_date') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="budget_amount">Budget Amount</label>
            <input id="budget_amount" name="budget_amount" type="number" min="0" step="0.01" value="{{ old('budget_amount', $project->budget_amount ?? 0) }}">
            @error('budget_amount') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="quantity_unit">Quantity Unit</label>
            <input id="quantity_unit" name="quantity_unit" type="text" value="{{ old('quantity_unit', $project->quantity_unit ?? '') }}" placeholder="Cum, MT, Sqm, Km">
            @error('quantity_unit') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="planned_quantity">Planned Quantity</label>
            <input id="planned_quantity" name="planned_quantity" type="number" min="0" step="0.001" value="{{ old('planned_quantity', $project->planned_quantity ?? 0) }}">
            @error('planned_quantity') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="executed_quantity">Executed Quantity</label>
            <input id="executed_quantity" name="executed_quantity" type="number" min="0" step="0.001" value="{{ old('executed_quantity', $project->executed_quantity ?? 0) }}">
            @error('executed_quantity') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="estimated_cost">Estimated Cost</label>
            <input id="estimated_cost" name="estimated_cost" type="number" min="0" step="0.01" value="{{ old('estimated_cost', $project->estimated_cost ?? 0) }}">
            @error('estimated_cost') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="actual_cost">Actual Cost</label>
            <input id="actual_cost" name="actual_cost" type="number" min="0" step="0.01" value="{{ old('actual_cost', $project->actual_cost ?? 0) }}">
            @error('actual_cost') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="progress_percent">Progress %</label>
            <input id="progress_percent" name="progress_percent" type="number" min="0" max="100" value="{{ old('progress_percent', $project->progress_percent ?? 0) }}">
            @error('progress_percent') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field full">
            <label for="description">Project Description</label>
            <textarea id="description" name="description">{{ old('description', $project->description ?? '') }}</textarea>
            @error('description') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>
</section>
