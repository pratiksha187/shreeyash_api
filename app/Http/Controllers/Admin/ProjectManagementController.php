<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCompanyAdmin();

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Project::STATUSES))],
            'search' => ['nullable', 'string', 'max:255'],
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
            ->route('admin.projects.show', $project)
            ->with('success', 'Project added successfully. You can now assign tasks to engineers and supervisors.');
    }

    public function show(int $project): View
    {
        $this->ensureCompanyAdmin();

        $project = $this->findProject($project);
        $project->load(['planningManager:id,name,designation']);

        $tasks = $project->tasks()
            ->with(['engineer:id,name,mobile,designation', 'supervisor:id,name,mobile,designation'])
            ->orderByRaw("FIELD(status, 'blocked', 'in_progress', 'pending', 'completed', 'cancelled')")
            ->orderBy('due_date')
            ->latest()
            ->paginate(20);

        return view('admin.projects.show', [
            'project' => $project,
            'tasks' => $tasks,
            'employees' => $this->employees(),
            'statuses' => Project::STATUSES,
            'taskStatuses' => ProjectTask::STATUSES,
            'priorities' => ProjectTask::PRIORITIES,
            'taskSummary' => $this->taskSummary($project),
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

    private function projectData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'site_location' => ['nullable', 'string', 'max:255'],
            'planning_manager_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['required', Rule::in(array_keys(Project::STATUSES))],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->ensureEmployeeId($data['planning_manager_id'] ?? null, 'planning_manager_id');

        return $data;
    }

    private function taskData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'work_area' => ['nullable', 'string', 'max:255'],
            'assigned_engineer_id' => ['nullable', 'integer'],
            'assigned_supervisor_id' => ['nullable', 'integer'],
            'priority' => ['required', Rule::in(array_keys(ProjectTask::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(ProjectTask::STATUSES))],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'completion_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->ensureEmployeeId($data['assigned_engineer_id'] ?? null, 'assigned_engineer_id');
        $this->ensureEmployeeId($data['assigned_supervisor_id'] ?? null, 'assigned_supervisor_id');

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
