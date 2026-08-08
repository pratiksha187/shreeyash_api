<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectBoqItem;
use App\Models\ProjectTask;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ZipArchive;

class ProjectManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCompanyAdmin();

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Project::STATUSES))],
            'search' => ['nullable', 'string', 'max:255'],
            'alert_date' => ['nullable', 'date'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));

        $projects = Project::query()
            ->forCurrentCompany()
            ->with(['planningManager:id,name,designation'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed'),
                'tasks as overdue_tasks_count' => fn ($query) => $query
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->whereDate('due_date', '<', today()),
            ])
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('client_name', 'like', '%'.$search.'%')
                        ->orWhere('site_location', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
            'statuses' => Project::STATUSES,
            'selectedStatus' => $filters['status'] ?? null,
            'search' => $search,
            'summary' => $this->summary(),
            'performance' => $this->performance(),
            'todoRows' => $this->todoRows(),
        ]);
    }

    public function create(): View
    {
        $this->ensureCompanyAdmin();

        return view('admin.projects.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $project = Project::query()->create($this->projectData($request));

        return redirect()
            ->route('admin.projects.structure', $project)
            ->with('success', 'Project added successfully. Now add phase, layer, task, and sub-task structure.');
    }

    public function show(int $project): View
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);
        $project->load(['planningManager:id,name,designation']);

        $tasks = $project->tasks()
            ->with(['engineer:id,name,mobile,designation', 'supervisor:id,name,mobile,designation', 'material:id,name,unit'])
            ->orderByRaw("FIELD(status, 'blocked', 'in_progress', 'pending', 'completed', 'cancelled')")
            ->orderBy('due_date')
            ->latest()
            ->paginate(20);
        $boqItems = $project->boqItems()
            ->where('item_type', 'item')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['boq_no', 'task_name', 'unit', 'scope_qty', 'rate']);

        return view('admin.projects.show', [
            'project' => $project,
            'tasks' => $tasks,
            'boqItems' => $boqItems,
            'materials' => $this->materials(),
            'employees' => $this->employees(),
            'statuses' => Project::STATUSES,
            'taskStatuses' => ProjectTask::STATUSES,
            'priorities' => ProjectTask::PRIORITIES,
            'taskSummary' => $this->taskSummary($project),
            'costSummary' => $this->costSummary($project),
        ]);
    }

    public function edit(int $project): View
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);

        return view('admin.projects.edit', [
            ...$this->formData(),
            'project' => $project,
        ]);
    }

    public function update(Request $request, int $project): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);

        $data = $this->projectData($request);
        $data['completed_at'] = $data['status'] === 'completed'
            ? ($project->completed_at ?: now()->toDateString())
            : null;

        $project->update($data);

        return redirect()
            ->route('admin.projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function storeTask(Request $request, int $project): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);

        $project->tasks()->create([
            ...$this->taskData($request),
            'created_by' => session('admin_user_id'),
        ]);

        $this->refreshProjectProgress($project);

        return back()->with('success', 'Task assigned successfully.');
    }

    public function updateTask(Request $request, int $project, int $task): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);
        $task = ProjectTask::query()
            ->forCurrentCompany()
            ->where('project_id', $project->id)
            ->findOrFail($task);

        if ((int) $task->project_id !== (int) $project->id) {
            abort(404);
        }

        $data = $this->taskData($request);
        $data['completed_at'] = $data['status'] === 'completed'
            ? ($task->completed_at ?: now())
            : null;

        $task->update($data);
        $this->refreshProjectProgress($project);

        return back()->with('success', 'Task updated successfully.');
    }

    public function structure(int $project): View
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);
        $items = $project->tasks()
            ->with('children.children.children')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.projects.structure', [
            'project' => $project,
            'items' => $items,
            'roots' => $items->whereNull('parent_task_id'),
            'structureTypes' => ProjectTask::STRUCTURE_TYPES,
        ]);
    }

    public function storeStructureItem(Request $request, int $project): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);

        $project->tasks()->create([
            ...$this->structureItemData($request, $project),
            'priority' => 'medium',
            'status' => 'pending',
            'progress_percent' => 0,
            'created_by' => session('admin_user_id'),
        ]);

        return back()->with('success', 'Project structure row added successfully.');
    }

    public function destroyStructureItem(int $project, int $task): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);
        $task = ProjectTask::query()
            ->forCurrentCompany()
            ->where('project_id', $project->id)
            ->with('children.children.children')
            ->findOrFail($task);

        $this->deleteTaskTree($task);

        return back()->with('success', 'Structure row deleted successfully.');
    }

    public function boq(int $project): View
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);
        $items = $project->boqItems()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $childItems = $items->where('item_type', 'item')->groupBy(fn (ProjectBoqItem $item) => $item->parent_boq_no ?: '__ungrouped');
        $groupTotals = $childItems->map(function ($children) {
            $scopeQty = (float) $children->sum('scope_qty');
            $doneQty = (float) $children->sum('done_qty');
            $amount = (float) $children->sum(fn (ProjectBoqItem $child) => (float) $child->scope_qty * (float) $child->rate);

            return [
                'rate' => 0,
                'tender_qty' => (float) $children->sum('tender_qty'),
                'scope_qty' => $scopeQty,
                'amount' => $amount,
                'subcontractor_done_qty' => (float) $children->sum('subcontractor_done_qty'),
                'self_done_qty' => (float) $children->sum('self_done_qty'),
                'done_qty' => $doneQty,
                'balance_qty' => (float) $children->sum('balance_qty'),
                'balance_estimate' => (float) $children->sum('balance_estimate'),
                'billed_amount' => (float) $children->sum('billed_amount'),
                'dpr_unbilled_amount' => (float) $children->sum('dpr_unbilled_amount'),
                'progress_percent' => $scopeQty > 0 ? min(100, round(($doneQty / $scopeQty) * 100, 2)) : 0,
            ];
        });

        return view('admin.projects.boq', [
            'project' => $project,
            'items' => $items,
            'groupItems' => $items->where('item_type', 'group'),
            'childItems' => $childItems,
            'groupTotals' => $groupTotals,
            'types' => ProjectBoqItem::TYPES,
            'summary' => $this->boqSummary($project),
        ]);
    }

    public function storeBoqItem(Request $request, int $project): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);

        $project->boqItems()->create($this->boqItemData($request, $project));

        return back()->with('success', 'BOQ item added successfully.');
    }

    public function destroyBoqItem(int $project, int $boqItem): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);

        ProjectBoqItem::query()
            ->forCurrentCompany()
            ->where('project_id', $project->id)
            ->findOrFail($boqItem)
            ->delete();

        return back()->with('success', 'BOQ item deleted successfully.');
    }

    public function downloadBoqTemplate(int $project)
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);
        $path = storage_path('app/boq-template-'.$project->id.'-'.uniqid().'.xlsx');

        $this->writeBoqTemplate($path, $project);

        return response()
            ->download($path, 'boq-template-'.($project->code ?: 'project-'.$project->id).'.xlsx')
            ->deleteFileAfterSend(true);
    }

    public function exportBoq(int $project)
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);
        $path = storage_path('app/boq-export-'.$project->id.'-'.uniqid().'.xlsx');

        $this->writeBoqExport($path, $project);

        return response()
            ->download($path, 'boq-export-'.($project->code ?: 'project-'.$project->id).'.xlsx')
            ->deleteFileAfterSend(true);
    }

    public function importBoq(Request $request, int $project): RedirectResponse
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);

        $data = $request->validate([
            'boq_file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120'],
            'replace_existing' => ['nullable', 'boolean'],
        ]);

        $rows = $this->readBoqImportRows($data['boq_file']->getRealPath(), $data['boq_file']->getClientOriginalExtension());

        if ($rows === []) {
            return back()->with('error', 'No BOQ rows found in uploaded file.');
        }

        DB::transaction(function () use ($project, $rows, $data) {
            if (! empty($data['replace_existing'])) {
                $project->boqItems()->delete();
            }

            $currentGroupNo = null;

            foreach ($rows as $index => $row) {
                $itemData = $this->normalizeBoqImportRow($row, $project, $index + 1);

                if ($itemData['item_type'] === 'group') {
                    $itemData['boq_no'] = $itemData['boq_no'] ?: 'H-'.($index + 1);
                    $currentGroupNo = $itemData['boq_no'];
                } elseif (empty($itemData['parent_boq_no']) && $currentGroupNo) {
                    $itemData['parent_boq_no'] = $currentGroupNo;
                }

                $project->boqItems()->create($itemData);
            }
        });

        return back()->with('success', count($rows).' BOQ rows imported successfully.');
    }

    private function projectData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'site_location' => ['nullable', 'string', 'max:255'],
            'work_order_number' => ['nullable', 'string', 'max:255'],
            'work_order_date' => ['nullable', 'date'],
            'boq_reference' => ['nullable', 'string', 'max:255'],
            'sor_reference' => ['nullable', 'string', 'max:255'],
            'planning_manager_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'quantity_unit' => ['nullable', 'string', 'max:40'],
            'planned_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'executed_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'status' => ['required', Rule::in(array_keys(Project::STATUSES))],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->ensureEmployeeId($data['planning_manager_id'] ?? null, 'planning_manager_id');

        foreach (['budget_amount', 'planned_quantity', 'executed_quantity', 'estimated_cost', 'actual_cost', 'progress_percent'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        return $data;
    }

    private function taskData(Request $request): array
    {
        $data = $request->validate([
            'parent_task_id' => ['nullable', 'integer'],
            'structure_type' => ['nullable', Rule::in(array_keys(ProjectTask::STRUCTURE_TYPES))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'title' => ['required', 'string', 'max:255'],
            'work_area' => ['nullable', 'string', 'max:255'],
            'boq_item_number' => ['nullable', 'string', 'max:80'],
            'sor_item_number' => ['nullable', 'string', 'max:80'],
            'assigned_engineer_id' => ['nullable', 'integer'],
            'assigned_supervisor_id' => ['nullable', 'integer'],
            'priority' => ['required', Rule::in(array_keys(ProjectTask::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(ProjectTask::STATUSES))],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'quantity_unit' => ['nullable', 'string', 'max:40'],
            'material_template' => ['nullable', 'string', 'max:5000'],
            'material_id' => ['nullable', 'integer'],
            'opening_stock_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'receipt_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'issue_consumption_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'return_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'closing_stock_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'planned_material_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'planned_labour_count' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'planned_machinery_count' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'variance_limit_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'planned_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'executed_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'planned_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'completion_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->ensureEmployeeId($data['assigned_engineer_id'] ?? null, 'assigned_engineer_id');
        $this->ensureEmployeeId($data['assigned_supervisor_id'] ?? null, 'assigned_supervisor_id');
        $this->ensureMaterialId($data['material_id'] ?? null, 'material_id');

        $data['structure_type'] = $data['structure_type'] ?? 'task';

        foreach ([
            'estimated_hours',
            'actual_hours',
            'opening_stock_qty',
            'receipt_qty',
            'issue_consumption_qty',
            'return_qty',
            'closing_stock_qty',
            'planned_material_qty',
            'planned_labour_count',
            'planned_machinery_count',
            'variance_limit_percent',
            'planned_quantity',
            'executed_quantity',
            'rate',
            'planned_cost',
            'actual_cost',
            'progress_percent',
        ] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        if ((float) $data['variance_limit_percent'] <= 0) {
            $data['variance_limit_percent'] = 10;
        }

        $data['closing_stock_qty'] = max(
            ((float) $data['opening_stock_qty'] + (float) $data['receipt_qty']) - (float) $data['issue_consumption_qty'] - (float) $data['return_qty'],
            0
        );

        $data['sort_order'] = $data['sort_order'] ?? 0;

        $plannedQuantity = (float) ($data['planned_quantity'] ?? 0);
        $rate = (float) ($data['rate'] ?? 0);

        if ((float) ($data['planned_cost'] ?? 0) <= 0 && $plannedQuantity > 0 && $rate > 0) {
            $data['planned_cost'] = round($plannedQuantity * $rate, 2);
        }

        return $data;
    }

    private function structureItemData(Request $request, Project $project): array
    {
        $data = $request->validate([
            'structure_type' => ['required', Rule::in(array_keys(ProjectTask::STRUCTURE_TYPES))],
            'parent_task_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'work_area' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        if (! empty($data['parent_task_id'])) {
            ProjectTask::query()
                ->forCurrentCompany()
                ->where('project_id', $project->id)
                ->whereKey($data['parent_task_id'])
                ->firstOrFail();
        }

        $data['sort_order'] = $data['sort_order'] ?? ((int) $project->tasks()->max('sort_order') + 10);

        return $data;
    }

    private function summary(): array
    {
        return [
            'total' => Project::query()->forCurrentCompany()->count(),
            'active' => Project::query()->forCurrentCompany()->where('status', 'active')->count(),
            'tasks_pending' => ProjectTask::query()->forCurrentCompany()->whereIn('status', ['pending', 'in_progress', 'blocked'])->count(),
            'tasks_overdue' => ProjectTask::query()
                ->forCurrentCompany()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('due_date', '<', today())
                ->count(),
        ];
    }

    private function performance()
    {
        return User::query()
            ->forCurrentCompany()
            ->employees()
            ->select('users.id', 'users.name', 'users.designation')
            ->selectSub(function ($query) {
                $query->from('project_tasks')
                    ->selectRaw('count(*)')
                    ->whereColumn('project_tasks.assigned_engineer_id', 'users.id')
                    ->where('project_tasks.company_id', app(Tenant::class)->id());
            }, 'assigned_tasks_count')
            ->selectSub(function ($query) {
                $query->from('project_tasks')
                    ->selectRaw('count(*)')
                    ->whereColumn('project_tasks.assigned_engineer_id', 'users.id')
                    ->where('project_tasks.company_id', app(Tenant::class)->id())
                    ->where('status', 'completed');
            }, 'completed_tasks_count')
            ->selectSub(function ($query) {
                $query->from('project_tasks')
                    ->selectRaw('coalesce(sum(actual_hours), 0)')
                    ->whereColumn('project_tasks.assigned_engineer_id', 'users.id')
                    ->where('project_tasks.company_id', app(Tenant::class)->id())
                    ->where('status', 'completed');
            }, 'completed_hours')
            ->orderByDesc('completed_tasks_count')
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function todoRows(): array
    {
        return [
            [
                'label' => 'Project Tasks',
                'approval' => ProjectTask::query()->forCurrentCompany()->where('status', 'pending')->count(),
                'next' => ProjectTask::query()->forCurrentCompany()->where('status', 'in_progress')->count(),
                'rejected' => ProjectTask::query()->forCurrentCompany()->where('status', 'blocked')->count(),
            ],
            [
                'label' => 'Work Order',
                'approval' => Project::query()->forCurrentCompany()->whereNotNull('work_order_number')->count(),
                'next' => Project::query()->forCurrentCompany()->whereNull('work_order_number')->count(),
                'rejected' => 0,
            ],
            [
                'label' => 'BOQ / SOR',
                'approval' => ProjectTask::query()->forCurrentCompany()->whereNotNull('boq_item_number')->count(),
                'next' => ProjectTask::query()->forCurrentCompany()->whereNotNull('sor_item_number')->count(),
                'rejected' => 0,
            ],
            [
                'label' => 'DPR',
                'approval' => \App\Models\DailyProgressReport::query()->forCurrentCompany()->count(),
                'next' => 0,
                'rejected' => 0,
            ],
            [
                'label' => 'RA Bill',
                'approval' => 0,
                'next' => 0,
                'rejected' => 0,
            ],
            [
                'label' => 'Subcontractor Bill',
                'approval' => 0,
                'next' => 0,
                'rejected' => 0,
            ],
        ];
    }

    private function taskSummary(Project $project): array
    {
        return [
            'total' => $project->tasks()->count(),
            'pending' => $project->tasks()->where('status', 'pending')->count(),
            'in_progress' => $project->tasks()->where('status', 'in_progress')->count(),
            'completed' => $project->tasks()->where('status', 'completed')->count(),
            'overdue' => $project->tasks()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('due_date', '<', today())
                ->count(),
        ];
    }

    private function costSummary(Project $project): array
    {
        $taskTotals = $project->tasks()
            ->selectRaw('
                coalesce(sum(planned_quantity), 0) as planned_quantity,
                coalesce(sum(executed_quantity), 0) as executed_quantity,
                coalesce(sum(planned_cost), 0) as planned_cost,
                coalesce(sum(actual_cost), 0) as actual_cost,
                coalesce(sum((case when planned_quantity > executed_quantity then planned_quantity - executed_quantity else 0 end) * rate), 0) as cost_to_complete
            ')
            ->first();

        $plannedQuantity = (float) ($taskTotals?->planned_quantity ?: $project->planned_quantity);
        $executedQuantity = (float) ($taskTotals?->executed_quantity ?: $project->executed_quantity);
        $plannedCost = (float) ($taskTotals?->planned_cost ?: ($project->estimated_cost ?: $project->budget_amount));
        $actualCost = (float) ($taskTotals?->actual_cost ?: $project->actual_cost);
        $costToComplete = (float) ($taskTotals?->cost_to_complete ?: max($plannedCost - $actualCost, 0));
        $quantityBalance = max($plannedQuantity - $executedQuantity, 0);
        $budgetVariance = $plannedCost > 0 ? $plannedCost - $actualCost : 0;

        return [
            'planned_quantity' => $plannedQuantity,
            'executed_quantity' => $executedQuantity,
            'quantity_balance' => $quantityBalance,
            'planned_cost' => $plannedCost,
            'actual_cost' => $actualCost,
            'cost_to_complete' => $costToComplete,
            'budget_variance' => $budgetVariance,
            'planned_material_qty' => (float) $project->tasks()->sum('planned_material_qty'),
            'planned_labour_count' => (int) $project->tasks()->sum('planned_labour_count'),
            'planned_machinery_count' => (int) $project->tasks()->sum('planned_machinery_count'),
            'opening_stock_qty' => (float) $project->tasks()->sum('opening_stock_qty'),
            'receipt_qty' => (float) $project->tasks()->sum('receipt_qty'),
            'issue_consumption_qty' => (float) $project->tasks()->sum('issue_consumption_qty'),
            'return_qty' => (float) $project->tasks()->sum('return_qty'),
            'closing_stock_qty' => (float) $project->tasks()->sum('closing_stock_qty'),
            'variance_alerts' => $this->varianceAlerts($project),
        ];
    }

    private function varianceAlerts(Project $project): int
    {
        return $project->tasks()
            ->get(['planned_cost', 'actual_cost', 'planned_quantity', 'executed_quantity', 'variance_limit_percent'])
            ->filter(function (ProjectTask $task) {
                $limit = max((float) $task->variance_limit_percent, 0);

                if ((float) $task->planned_cost > 0) {
                    $costVariance = abs(((float) $task->actual_cost - (float) $task->planned_cost) / (float) $task->planned_cost) * 100;
                    if ($costVariance > $limit) {
                        return true;
                    }
                }

                if ((float) $task->planned_quantity > 0) {
                    $quantityVariance = abs(((float) $task->executed_quantity - (float) $task->planned_quantity) / (float) $task->planned_quantity) * 100;
                    return $quantityVariance > $limit;
                }

                return false;
            })
            ->count();
    }

    private function boqItemData(Request $request, Project $project): array
    {
        $data = $request->validate([
            'boq_no' => ['nullable', 'string', 'max:80'],
            'parent_boq_no' => ['nullable', 'string', 'max:80'],
            'item_type' => ['required', Rule::in(array_keys(ProjectBoqItem::TYPES))],
            'group_name' => ['nullable', 'string', 'max:255'],
            'task_name' => ['required', 'string', 'max:1000'],
            'unit' => ['nullable', 'string', 'max:40'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'tender_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'scope_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'subcontractor_done_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'self_done_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'done_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'billed_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'dpr_unbilled_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        return $this->normalizeBoqNumbers([
            ...$data,
            'sort_order' => $data['sort_order'] ?? ((int) $project->boqItems()->max('sort_order') + 10),
        ]);
    }

    private function boqSummary(Project $project): array
    {
        $totals = $project->boqItems()
            ->where('item_type', 'item')
            ->selectRaw('
                count(*) as total_items,
                coalesce(sum(scope_qty * rate), 0) as boq_amount,
                coalesce(sum(tender_qty), 0) as tender_qty,
                coalesce(sum(scope_qty), 0) as scope_qty,
                coalesce(sum(done_qty), 0) as done_qty,
                coalesce(sum(balance_qty), 0) as balance_qty,
                coalesce(sum(balance_estimate), 0) as balance_estimate,
                coalesce(sum(billed_amount), 0) as billed_amount,
                coalesce(sum(dpr_unbilled_amount), 0) as dpr_unbilled_amount
            ')
            ->first();

        return [
            'total_items' => (int) ($totals?->total_items ?? 0),
            'boq_amount' => (float) ($totals?->boq_amount ?? 0),
            'tender_qty' => (float) ($totals?->tender_qty ?? 0),
            'scope_qty' => (float) ($totals?->scope_qty ?? 0),
            'done_qty' => (float) ($totals?->done_qty ?? 0),
            'balance_qty' => (float) ($totals?->balance_qty ?? 0),
            'balance_estimate' => (float) ($totals?->balance_estimate ?? 0),
            'billed_amount' => (float) ($totals?->billed_amount ?? 0),
            'dpr_unbilled_amount' => (float) ($totals?->dpr_unbilled_amount ?? 0),
        ];
    }

    private function normalizeBoqImportRow(array $row, Project $project, int $index): array
    {
        $get = function (array $keys) use ($row): mixed {
            foreach ($keys as $key) {
                $normalized = $this->normalizeImportHeader($key);
                if (array_key_exists($normalized, $row)) {
                    return $row[$normalized];
                }
            }

            return null;
        };

        $type = strtolower(trim((string) $get(['type', 'item type'])));
        $description = $get(['description', 'group/task name', 'task name', 'item name']);
        $total = $get(['total', 'scope qty', 'scope quantity']);
        $rate = $get(['rate']);
        $isGroup = $type === 'group' || ($type === '' && $description && $this->numberValue($total) <= 0 && $this->numberValue($rate) <= 0);

        return $this->normalizeBoqNumbers([
            'boq_no' => $get(['s.n', 'sn', 's no', 'boq no', 'boq number', 'boq']),
            'parent_boq_no' => $get(['parent boq no', 'parent boq number', 'parent']),
            'item_type' => $isGroup ? 'group' : 'item',
            'group_name' => $get(['group name', 'group']),
            'task_name' => $description ?: 'BOQ item '.$index,
            'unit' => $get(['uon', 'uom', 'unit']),
            'rate' => $rate,
            'tender_qty' => $get(['tender qty', 'tender quantity']),
            'scope_qty' => $total,
            'subcontractor_done_qty' => $get(['sub co done qty', 'subcontractor done qty', 'sub co. done']),
            'self_done_qty' => $get(['self done qty']),
            'done_qty' => $get(['done qty', 'done quantity']),
            'billed_amount' => $get(['billed amount']),
            'dpr_unbilled_amount' => $get(['dpr unbilled amount', 'dpr unbilled']),
            'sort_order' => $get(['sort order']) ?: ((int) $project->boqItems()->max('sort_order') + ($index * 10)),
        ]);
    }

    private function normalizeBoqNumbers(array $data): array
    {
        foreach (['boq_no', 'parent_boq_no', 'group_name', 'task_name', 'unit'] as $field) {
            $data[$field] = trim((string) ($data[$field] ?? '')) ?: null;
        }

        $data['task_name'] = $data['task_name'] ?: 'BOQ item';
        $data['item_type'] = $data['item_type'] ?? 'item';

        foreach ([
            'rate',
            'tender_qty',
            'scope_qty',
            'subcontractor_done_qty',
            'self_done_qty',
            'done_qty',
            'billed_amount',
            'dpr_unbilled_amount',
        ] as $field) {
            $data[$field] = $this->numberValue($data[$field] ?? 0);
        }

        if ($data['done_qty'] <= 0) {
            $data['done_qty'] = $data['subcontractor_done_qty'] + $data['self_done_qty'];
        }

        $data['balance_qty'] = max($data['scope_qty'] - $data['done_qty'], 0);
        $data['balance_estimate'] = round($data['balance_qty'] * $data['rate'], 2);
        $data['progress_percent'] = $data['scope_qty'] > 0
            ? min(100, round(($data['done_qty'] / $data['scope_qty']) * 100, 2))
            : 0;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function readBoqImportRows(string $path, string $extension): array
    {
        return strtolower($extension) === 'xlsx'
            ? $this->readXlsxBoqRows($path)
            : $this->readCsvBoqRows($path);
    }

    private function readCsvBoqRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [];
        }

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $possibleHeaders = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $line);
                if (! $this->isBoqImportHeader($possibleHeaders)) {
                    continue;
                }

                $headers = $possibleHeaders;
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $line[$index] ?? null;
            }

            if ($this->hasImportValue($row)) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxBoqRows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = $this->xlsxSharedStrings($zip);
        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $worksheet) {
            return [];
        }

        $xml = simplexml_load_string($worksheet);

        if (! $xml || ! isset($xml->sheetData->row)) {
            return [];
        }

        $headers = null;
        $rows = [];

        foreach ($xml->sheetData->row as $xmlRow) {
            $line = [];

            foreach ($xmlRow->c as $cell) {
                $attributes = $cell->attributes();
                $reference = (string) ($attributes['r'] ?? '');
                $column = $this->xlsxColumnIndex($reference);
                $line[$column] = $this->xlsxCellValue($cell, $sharedStrings);
            }

            if ($headers === null) {
                ksort($line);
                $possibleHeaders = [];
                foreach ($line as $column => $header) {
                    $possibleHeaders[$column] = $this->normalizeImportHeader((string) $header);
                }

                if (! $this->isBoqImportHeader($possibleHeaders)) {
                    continue;
                }

                $headers = $possibleHeaders;
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $line[$index] ?? null;
            }

            if ($this->hasImportValue($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function writeBoqTemplate(string $path, Project $project): void
    {
        $rows = $this->boqExcelRows($project);

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxWorksheet($rows, ['header_row' => 4]));
        $zip->close();
    }

    private function writeBoqExport(string $path, Project $project): void
    {
        $items = $project->boqItems()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $dataRows = $this->boqExportRows($items);
        $totalAmount = (float) $items
            ->where('item_type', 'item')
            ->sum(fn (ProjectBoqItem $item) => (float) $item->scope_qty * (float) $item->rate);
        $rows = $this->boqExcelRows($project, $dataRows, $totalAmount);

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxWorksheet($rows, ['header_row' => 4]));
        $zip->close();
    }

    private function boqExportRows($items): array
    {
        $rows = [];
        $groups = $items->where('item_type', 'group');
        $childItems = $items->where('item_type', 'item')->groupBy(fn (ProjectBoqItem $item) => $item->parent_boq_no ?: '__ungrouped');

        foreach ($groups as $group) {
            $rows[] = $this->boqExportRow($group);

            foreach ($childItems->get($group->boq_no, collect()) as $item) {
                $rows[] = $this->boqExportRow($item);
            }
        }

        foreach ($childItems->get('__ungrouped', collect()) as $item) {
            $rows[] = $this->boqExportRow($item);
        }

        return $rows;
    }

    private function boqExportRow(ProjectBoqItem $item): array
    {
        if ($item->item_type === 'group') {
            return [
                $item->boq_no,
                $item->task_name,
                '',
                '',
                '',
                '',
            ];
        }

        return [
            $item->boq_no,
            $item->task_name,
            $item->unit,
            (float) $item->scope_qty,
            (float) $item->rate,
            round((float) $item->scope_qty * (float) $item->rate, 2),
        ];
    }

    private function boqExcelRows(Project $project, array $dataRows = [], ?float $totalAmount = null): array
    {
        $projectLine = strtoupper($project->name);
        $locationLine = strtoupper(trim(($project->code ?: 'PLOT').' '.($project->site_location ?: $project->name)));

        $rows = [
            [$projectLine, '', '', '', '', ''],
            ['BILL OF QUANTITIES (BOQ)', '', '', '', '', ''],
            ['', '', '', '', '', ''],
            [$locationLine, '', '', 'As per BOQ', '', ''],
            ['S. N', 'Description', 'UON', 'Total', 'Rate', 'Amount'],
            ...$dataRows,
        ];

        if ($dataRows !== []) {
            $rows[] = ['', '', '', '', '', ''];
            $rows[] = ['Total Amount', '', '', '', '', round((float) $totalAmount, 2)];
        }

        return $rows;
    }

    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $contents = $zip->getFromName('xl/sharedStrings.xml');

        if (! $contents) {
            return [];
        }

        $xml = simplexml_load_string($contents);
        $strings = [];

        foreach ($xml->si ?? [] as $item) {
            $strings[] = (string) ($item->t ?? '');
        }

        return $strings;
    }

    private function xlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings): mixed
    {
        $type = (string) ($cell->attributes()['t'] ?? '');

        if ($type === 's') {
            return $sharedStrings[(int) ($cell->v ?? 0)] ?? null;
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        return (string) ($cell->v ?? '');
    }

    private function xlsxColumnIndex(string $reference): int
    {
        preg_match('/^([A-Z]+)/', $reference, $matches);
        $letters = $matches[1] ?? 'A';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function xlsxWorksheet(array $rows, array $options = []): string
    {
        $headerRow = (int) ($options['header_row'] ?? 0);
        $totalRow = $this->xlsxTotalRowIndex($rows);
        $xmlRows = [];
        $maxColumns = 6;

        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            $height = $this->xlsxRowHeight($rowIndex, $row, $headerRow, $totalRow);

            for ($columnIndex = 0; $columnIndex < $maxColumns; $columnIndex++) {
                $value = $row[$columnIndex] ?? '';
                $style = $this->xlsxCellStyle($rowIndex, $columnIndex, $row, $headerRow, $totalRow);
                $reference = $this->xlsxColumnName($columnIndex + 1).($rowIndex + 1);
                if (is_numeric($value)) {
                    $cells[] = '<c r="'.$reference.'" s="'.$style.'"><v>'.$value.'</v></c>';
                } else {
                    $cells[] = '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t>'.$this->xmlEscape((string) $value).'</t></is></c>';
                }
            }
            $xmlRows[] = '<row r="'.($rowIndex + 1).'"'.$height.'>'.implode('', $cells).'</row>';
        }

        $headerExcelRow = $headerRow + 1;
        $mergeCells = [
            '<mergeCell ref="A1:F1"/>',
            '<mergeCell ref="A2:F2"/>',
            '<mergeCell ref="A4:C4"/>',
            '<mergeCell ref="D4:F4"/>',
        ];

        if ($totalRow !== null) {
            $mergeCells[] = '<mergeCell ref="A'.($totalRow + 1).':E'.($totalRow + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$headerExcelRow.'" topLeftCell="A'.($headerExcelRow + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'
            .'<col min="1" max="1" width="10" customWidth="1"/>'
            .'<col min="2" max="2" width="72" customWidth="1"/>'
            .'<col min="3" max="3" width="10" customWidth="1"/>'
            .'<col min="4" max="4" width="13" customWidth="1"/>'
            .'<col min="5" max="6" width="18" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>'.implode('', $xmlRows).'</sheetData>'
            .'<autoFilter ref="A'.$headerExcelRow.':F'.$headerExcelRow.'"/>'
            .'<mergeCells count="'.count($mergeCells).'">'.implode('', $mergeCells).'</mergeCells>'
            .'</worksheet>';
    }

    private function xlsxCellStyle(int $rowIndex, int $columnIndex, array $row, int $headerRow, ?int $totalRow): int
    {
        if ($rowIndex === 0) {
            return 1;
        }

        if ($rowIndex === 1) {
            return 2;
        }

        if ($rowIndex === 3) {
            return $columnIndex >= 3 ? 3 : 2;
        }

        if ($rowIndex === $headerRow) {
            return 4;
        }

        if ($totalRow !== null && $rowIndex === $totalRow) {
            return $columnIndex === 5 ? 9 : 7;
        }

        if ($rowIndex > $headerRow && trim((string) ($row[1] ?? '')) !== '' && trim((string) ($row[2] ?? '')) === '' && trim((string) ($row[3] ?? '')) === '' && trim((string) ($row[4] ?? '')) === '') {
            return 5;
        }

        if ($rowIndex > $headerRow && $columnIndex >= 3) {
            return 8;
        }

        return 6;
    }

    private function xlsxRowHeight(int $rowIndex, array $row, int $headerRow, ?int $totalRow): string
    {
        if ($rowIndex === 2) {
            return ' ht="8" customHeight="1"';
        }

        if ($rowIndex === 3 || $rowIndex === $headerRow || $rowIndex === $totalRow) {
            return ' ht="24" customHeight="1"';
        }

        if ($rowIndex > $headerRow && strlen((string) ($row[1] ?? '')) > 80) {
            return ' ht="46" customHeight="1"';
        }

        return ' ht="20" customHeight="1"';
    }

    private function xlsxTotalRowIndex(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if (($row[0] ?? null) === 'Total Amount') {
                return $index;
            }
        }

        return null;
    }

    private function xlsxColumnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function xlsxWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="BOQ Template" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function xlsxStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="4">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="12"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="4">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFCC00"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF2F2F2"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color indexed="64"/></left><right style="thin"><color indexed="64"/></right><top style="thin"><color indexed="64"/></top><bottom style="thin"><color indexed="64"/></bottom><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="10">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="1" applyFont="1" applyBorder="1"><alignment horizontal="left"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="1" applyFont="1" applyBorder="1"><alignment horizontal="left"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="1" applyFont="1" applyBorder="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="3" borderId="1" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="1" applyFont="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="4" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right" vertical="top"/></xf>'
            .'<xf numFmtId="4" fontId="2" fillId="0" borderId="1" applyFont="1" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function normalizeImportHeader(string $header): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(trim($header))) ?: '';
    }

    private function hasImportValue(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function isBoqImportHeader(array $headers): bool
    {
        return in_array('description', $headers, true)
            && (in_array('sn', $headers, true) || in_array('boqno', $headers, true))
            && (in_array('uon', $headers, true) || in_array('uom', $headers, true) || in_array('unit', $headers, true));
    }

    private function numberValue(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function refreshProjectProgress(Project $project): void
    {
        $tasks = $project->tasks()->get(['status', 'progress_percent']);

        if ($tasks->isEmpty()) {
            return;
        }

        $project->forceFill([
            'progress_percent' => (int) round($tasks->avg('progress_percent')),
            'status' => $tasks->every(fn (ProjectTask $task) => $task->status === 'completed') ? 'completed' : $project->status,
            'completed_at' => $tasks->every(fn (ProjectTask $task) => $task->status === 'completed') ? now()->toDateString() : $project->completed_at,
        ])->save();
    }

    private function deleteTaskTree(ProjectTask $task): void
    {
        $task->loadMissing('children');

        foreach ($task->children as $child) {
            $this->deleteTaskTree($child);
        }

        $task->delete();
    }

    private function formData(): array
    {
        return [
            'employees' => $this->employees(),
            'sites' => $this->sites(),
            'statuses' => Project::STATUSES,
        ];
    }

    private function employees()
    {
        return User::query()
            ->forCurrentCompany()
            ->employees()
            ->orderBy('name')
            ->get(['id', 'name', 'designation', 'mobile']);
    }

    private function materials()
    {
        return Material::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'material_type']);
    }

    private function sites()
    {
        return LabourSite::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);
    }

    private function ensureEmployeeId(mixed $employeeId, string $field): void
    {
        if (! $employeeId) {
            return;
        }

        if (User::query()->forCurrentCompany()->employees()->whereKey($employeeId)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'Selected employee was not found.',
        ]);
    }

    private function ensureMaterialId(mixed $materialId, string $field): void
    {
        if (! $materialId) {
            return;
        }

        if (Material::query()->forCurrentCompany()->whereKey($materialId)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'Selected material was not found.',
        ]);
    }

    private function findProject(int $project): Project
    {
        return Project::query()
            ->forCurrentCompany()
            ->findOrFail($project);
    }

    private function ensureCurrentCompany(Project|ProjectTask $record): void
    {
        $companyId = app(Tenant::class)->id();

        if ($companyId && (int) $record->company_id !== (int) $companyId) {
            abort(404);
        }
    }

    private function ensureCompanyAdmin(): void
    {
        if (session()->has('admin_company_id') && app(Tenant::class)->hasCompany()) {
            return;
        }

        abort(403, 'Please login with an employer/company admin account to manage projects.');
    }
}
