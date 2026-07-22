<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUpdate;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProjectTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->hasTable('project_tasks')) {
            return response()->json([
                'message' => 'Project task table is not available yet.',
                'project_tasks' => [],
            ], 503);
        }

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(ProjectTask::STATUSES))],
            'project_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tasks = $this->allocatedTaskQuery($request)
            ->with($this->taskRelations(limitUpdates: true))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['project_id']), fn ($query) => $query->where('project_id', $filters['project_id']))
            ->orderByRaw("FIELD(status, 'blocked', 'in_progress', 'pending', 'completed', 'cancelled')")
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->latest()
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn (ProjectTask $task) => $this->taskPayload($task, $request->user()->id));

        return response()->json([
            'message' => 'Allocated project tasks fetched successfully.',
            'project_tasks' => $tasks,
            'tasks' => $tasks,
        ]);
    }

    public function show(Request $request, int $projectTask): JsonResponse
    {
        if (! $this->hasTable('project_tasks')) {
            return response()->json([
                'message' => 'Project task table is not available yet.',
            ], 503);
        }

        $task = $this->allocatedTaskQuery($request)
            ->with($this->taskRelations(includeProjectDescription: true))
            ->findOrFail($projectTask);

        return response()->json([
            'message' => 'Project task fetched successfully.',
            'project_task' => $this->taskPayload($task, $request->user()->id),
            'task' => $this->taskPayload($task, $request->user()->id),
        ]);
    }

    public function update(Request $request, int $projectTask): JsonResponse
    {
        if (! $this->hasTable('project_tasks')) {
            return response()->json([
                'message' => 'Project task table is not available yet.',
            ], 503);
        }

        if (! $this->hasTable('project_task_updates')) {
            return response()->json([
                'message' => 'Project task update table is not available yet. Please create project_task_updates table first.',
            ], 503);
        }

        $task = $this->allocatedTaskQuery($request)->findOrFail($projectTask);

        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(ProjectTask::STATUSES))],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'completion_note' => ['nullable', 'string', 'max:5000'],
            'remark' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ]);

        $photoPath = $request->hasFile('photo')
            ? $this->storeTaskPhoto($request, $task)
            : null;

        if (($data['status'] ?? null) === 'completed') {
            $data['progress_percent'] = 100;
            $data['completed_at'] = $task->completed_at ?: now();
        } elseif (array_key_exists('status', $data)) {
            $data['completed_at'] = null;
        }

        $taskData = collect($data)
            ->only(['status', 'progress_percent', 'actual_hours', 'completion_note', 'completed_at'])
            ->all();

        $task->update($taskData);
        $update = $this->storeTaskUpdate($request, $task, $data, $photoPath);

        $this->refreshProjectProgress($task->project);
        $task->load($this->taskRelations());

        return response()->json([
            'message' => 'Project task tracking updated successfully.',
            'project_task' => $this->taskPayload($task, $request->user()->id),
            'task' => $this->taskPayload($task, $request->user()->id),
            'task_update' => $this->updatePayload($update),
        ]);
    }

    private function allocatedTaskQuery(Request $request)
    {
        $userId = $request->user()->id;

        return ProjectTask::query()
            ->forCurrentCompany()
            ->where(function ($query) use ($userId) {
                $query->where('assigned_engineer_id', $userId)
                    ->orWhere('assigned_supervisor_id', $userId);
            });
    }

    private function taskPayload(ProjectTask $task, int $userId): array
    {
        $task->loadMissing($this->taskRelations());

        return [
            'id' => $task->id,
            'project' => $task->project ? [
                'id' => $task->project->id,
                'name' => $task->project->name,
                'code' => $task->project->code,
                'client_name' => $task->project->client_name,
                'site_location' => $task->project->site_location,
                'status' => $task->project->status,
                'progress_percent' => $task->project->progress_percent,
                'target_date' => $task->project->target_date?->toDateString(),
                'description' => $task->project->description,
            ] : null,
            'title' => $task->title,
            'work_area' => $task->work_area,
            'priority' => $task->priority,
            'priority_label' => ProjectTask::PRIORITIES[$task->priority] ?? ucfirst($task->priority),
            'status' => $task->status,
            'status_label' => ProjectTask::STATUSES[$task->status] ?? ucfirst(str_replace('_', ' ', $task->status)),
            'start_date' => $task->start_date?->toDateString(),
            'due_date' => $task->due_date?->toDateString(),
            'completed_at' => $task->completed_at,
            'estimated_hours' => $task->estimated_hours,
            'actual_hours' => $task->actual_hours,
            'progress_percent' => $task->progress_percent,
            'description' => $task->description,
            'completion_note' => $task->completion_note,
            'latest_update' => $this->hasTable('project_task_updates') && $task->updates->sortByDesc('created_at')->first()
                ? $this->updatePayload($task->updates->sortByDesc('created_at')->first())
                : null,
            'updates' => $this->hasTable('project_task_updates')
                ? $task->updates
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(fn (ProjectTaskUpdate $update) => $this->updatePayload($update))
                : [],
            'my_role' => $this->allocatedRole($task, $userId),
            'engineer' => $task->engineer ? [
                'id' => $task->engineer->id,
                'name' => $task->engineer->name,
                'mobile' => $task->engineer->mobile,
                'designation' => $task->engineer->designation,
            ] : null,
            'supervisor' => $task->supervisor ? [
                'id' => $task->supervisor->id,
                'name' => $task->supervisor->name,
                'mobile' => $task->supervisor->mobile,
                'designation' => $task->supervisor->designation,
            ] : null,
            'is_overdue' => $task->due_date
                && ! in_array($task->status, ['completed', 'cancelled'], true)
                && $task->due_date->isBefore(today()),
            'assigned_at' => $task->created_at,
            'updated_at' => $task->updated_at,
        ];
    }

    private function taskRelations(bool $includeProjectDescription = false, bool $limitUpdates = false): array
    {
        $projectColumns = 'project:id,name,code,client_name,site_location,status,progress_percent,target_date'
            .($includeProjectDescription ? ',description' : '');

        $relations = [
            $projectColumns,
            'engineer:id,name,mobile,designation',
            'supervisor:id,name,mobile,designation',
        ];

        if ($this->hasTable('project_task_updates')) {
            $relations['updates'] = $limitUpdates
                ? fn ($query) => $query->latest()->limit(3)
                : fn ($query) => $query->latest();
            $relations[] = 'updates.user:id,name,mobile,designation';
        }

        return $relations;
    }

    private function storeTaskUpdate(Request $request, ProjectTask $task, array $data, ?string $photoPath): ProjectTaskUpdate
    {
        return $task->updates()->create([
            'user_id' => $request->user()->id,
            'status' => $data['status'] ?? $task->status,
            'progress_percent' => $data['progress_percent'] ?? $task->progress_percent,
            'actual_hours' => $data['actual_hours'] ?? $task->actual_hours,
            'remark' => $data['remark'] ?? ($data['completion_note'] ?? null),
            'photo_path' => $photoPath,
        ])->load('user:id,name,mobile,designation');
    }

    private function updatePayload(ProjectTaskUpdate $update): array
    {
        $update->loadMissing('user:id,name,mobile,designation');

        return [
            'id' => $update->id,
            'status' => $update->status,
            'progress_percent' => $update->progress_percent,
            'actual_hours' => $update->actual_hours,
            'remark' => $update->remark,
            'photo_path' => $update->photo_path,
            'photo_url' => $update->photoUrl(),
            'user' => $update->user ? [
                'id' => $update->user->id,
                'name' => $update->user->name,
                'mobile' => $update->user->mobile,
                'designation' => $update->user->designation,
            ] : null,
            'created_at' => $update->created_at,
            'updated_at' => $update->updated_at,
        ];
    }

    private function storeTaskPhoto(Request $request, ProjectTask $task): ?string
    {
        $photo = $request->file('photo');

        if (! $photo || ! $photo->isValid()) {
            return null;
        }

        Storage::disk('public')->makeDirectory('project_task_updates');

        $path = $photo->store(
            'project_task_updates/'.$request->user()->id.'/'.$task->id,
            'public'
        );

        $this->mirrorPhotoToPublicStorage($path);

        return $path;
    }

    private function mirrorPhotoToPublicStorage(string $path): void
    {
        $sourcePath = Storage::disk('public')->path($path);
        $targetPath = public_path('storage/'.str_replace('\\', '/', $path));

        if (! is_file($sourcePath)) {
            return;
        }

        File::ensureDirectoryExists(dirname($targetPath));
        File::copy($sourcePath, $targetPath);
    }

    private function allocatedRole(ProjectTask $task, int $userId): string
    {
        if ((int) $task->assigned_engineer_id === $userId && (int) $task->assigned_supervisor_id === $userId) {
            return 'engineer_supervisor';
        }

        if ((int) $task->assigned_engineer_id === $userId) {
            return 'engineer';
        }

        return 'supervisor';
    }

    private function refreshProjectProgress(?Project $project): void
    {
        if (! $project) {
            return;
        }

        $tasks = $project->tasks()->get(['status', 'progress_percent']);

        if ($tasks->isEmpty()) {
            return;
        }

        $allCompleted = $tasks->every(fn (ProjectTask $task) => $task->status === 'completed');

        $project->forceFill([
            'progress_percent' => (int) round($tasks->avg('progress_percent')),
            'status' => $allCompleted ? 'completed' : ($project->status === 'completed' ? 'active' : $project->status),
            'completed_at' => $allCompleted ? now()->toDateString() : null,
        ])->save();
    }

    private function hasTable(string $table): bool
    {
        return DB::connection(app(Tenant::class)->connectionName())
            ->getSchemaBuilder()
            ->hasTable($table);
    }
}
