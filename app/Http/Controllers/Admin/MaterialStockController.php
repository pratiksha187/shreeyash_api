<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\Material;
use App\Models\MaterialIssue;
use App\Models\MaterialRequest;
use App\Models\MaterialStock;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\StockMovement;
use App\Services\MaterialStockService;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaterialStockController extends Controller
{
    public function __construct(private readonly MaterialStockService $stockService)
    {
    }

    public function materials(): View
    {
        if (! $this->hasTable('materials')) {
            return view('admin.material-stock.materials', [
                'materials' => $this->emptyPaginator(),
            ])->with('error', 'Materials table is missing. Please create the materials table first.');
        }

        return view('admin.material-stock.materials', [
            'materials' => Material::query()
                ->forCurrentCompany()
                ->withSum('stocks', 'available_quantity')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function storeMaterial(Request $request): RedirectResponse
    {
        if (! $this->hasTable('materials')) {
            return back()->with('error', 'Materials table is missing. Please create the materials table first.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'material_type' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:50'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        Material::query()->create($data);

        return back()->with('success', 'Material added successfully.');
    }

    public function updateMaterial(Request $request, int $material): RedirectResponse
    {
        if (! $this->hasTable('materials')) {
            return back()->with('error', 'Materials table is missing. Please create the materials table first.');
        }

        $material = Material::query()->forCurrentCompany()->findOrFail($material);
        $this->ensureCurrentCompany($material);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'material_type' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:50'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active' => ['required', 'boolean'],
        ]);

        $material->update($data);

        return back()->with('success', 'Material updated successfully.');
    }

    public function stock(Request $request): View
    {
        if (! $this->hasTable('materials') || ! $this->hasTable('material_stocks')) {
            return view('admin.material-stock.stock', [
                'stocks' => $this->emptyPaginator(),
                'materials' => $this->activeMaterials(),
                'sites' => $this->activeSites(),
                'projects' => $this->activeProjects(),
                'projectTasks' => collect(),
                'selectedMaterialId' => null,
                'selectedSiteId' => null,
                'selectedProjectId' => null,
                'summary' => [
                    'materials' => $this->hasTable('materials') ? Material::query()->forCurrentCompany()->count() : 0,
                    'low_stock' => 0,
                    'stock_rows' => 0,
                ],
            ])->with('error', 'Material stock tables are missing. Please create materials and material_stocks tables first.');
        }

        $filters = $request->validate([
            'material_id' => ['nullable', 'integer'],
            'labour_site_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
        ]);

        $stocks = MaterialStock::query()
            ->forCurrentCompany()
            ->with(['material', 'site'])
            ->when(isset($filters['material_id']), fn ($query) => $query->where('material_id', $filters['material_id']))
            ->when(isset($filters['labour_site_id']), fn ($query) => $query->where('labour_site_id', $filters['labour_site_id']))
            ->whereHas('material')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.material-stock.stock', [
            'stocks' => $stocks,
            'materials' => $this->activeMaterials(),
            'sites' => $this->activeSites(),
            'projects' => $this->activeProjects(),
            'projectTasks' => $this->projectTasks($filters['project_id'] ?? null),
            'selectedMaterialId' => $filters['material_id'] ?? null,
            'selectedSiteId' => $filters['labour_site_id'] ?? null,
            'selectedProjectId' => $filters['project_id'] ?? null,
            'summary' => [
                'materials' => Material::query()->forCurrentCompany()->count(),
                'low_stock' => MaterialStock::query()
                    ->forCurrentCompany()
                    ->whereHas('material', fn ($query) => $query->whereColumn('material_stocks.available_quantity', '<=', 'materials.minimum_stock'))
                    ->count(),
                'stock_rows' => MaterialStock::query()->forCurrentCompany()->count(),
            ],
        ]);
    }

    public function adjustStock(Request $request): RedirectResponse
    {
        return back()->with('error', 'Direct stock adjustment is disabled. Add inward stock from Product Purchase and send outward stock from Material Requests.');
    }

    public function requests(Request $request): View
    {
        $this->ensureInventoryProjectColumns();

        if (! $this->hasTable('material_requests')) {
            return view('admin.material-stock.requests', [
                'requests' => $this->emptyPaginator(),
                'availableByRequest' => collect(),
                'materials' => collect(),
                'statuses' => MaterialRequest::STATUSES,
                'sites' => $this->activeSites(),
                'projects' => $this->activeProjects(),
                'projectTasks' => collect(),
                'selectedStatus' => null,
                'selectedSiteId' => null,
                'selectedProjectId' => null,
                'summary' => [
                    'pending' => 0,
                    'purchase_required' => 0,
                    'issued' => 0,
                ],
            ])->with('error', 'Material request table is missing. Please create the material_requests table first.');
        }

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(MaterialRequest::STATUSES)],
            'labour_site_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
        ]);

        $requests = MaterialRequest::query()
            ->forCurrentCompany()
            ->with($this->requestRelations())
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['labour_site_id']), fn ($query) => $query->where('labour_site_id', $filters['labour_site_id']))
            ->when(isset($filters['project_id']), fn ($query) => $query->where('project_id', $filters['project_id']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $availableByRequest = $requests->getCollection()->mapWithKeys(fn (MaterialRequest $request) => [
            $request->id => $request->material_id ? $this->totalAvailableQuantity((int) $request->material_id) : 0.0,
        ]);
        $stockRowsByRequest = $requests->getCollection()->mapWithKeys(fn (MaterialRequest $request) => [
            $request->id => $request->material_id ? $this->stockRowsForMaterial((int) $request->material_id) : collect(),
        ]);

        return view('admin.material-stock.requests', [
            'requests' => $requests,
            'availableByRequest' => $availableByRequest,
            'stockRowsByRequest' => $stockRowsByRequest,
            'materials' => $this->activeMaterials(),
            'statuses' => MaterialRequest::STATUSES,
            'sites' => $this->activeSites(),
            'projects' => $this->activeProjects(),
            'projectTasks' => $this->projectTasks($filters['project_id'] ?? null),
            'selectedStatus' => $filters['status'] ?? null,
            'selectedSiteId' => $filters['labour_site_id'] ?? null,
            'selectedProjectId' => $filters['project_id'] ?? null,
            'summary' => [
                'pending' => MaterialRequest::query()->forCurrentCompany()->where('status', 'pending')->count(),
                'purchase_required' => MaterialRequest::query()->forCurrentCompany()->where('status', 'purchase_required')->count(),
                'issued' => MaterialRequest::query()->forCurrentCompany()->where('status', 'issued')->count(),
            ],
        ]);
    }

    public function updateRequest(Request $request, int $materialRequestId): RedirectResponse
    {
        $this->ensureInventoryProjectColumns();

        if (! $this->hasTable('material_requests')) {
            return redirect()
                ->route('admin.material-requests.index')
                ->with('error', 'Material request table is missing. Please create the material_requests table first.');
        }

        $materialRequest = MaterialRequest::query()->forCurrentCompany()->findOrFail($materialRequestId);
        $this->ensureCurrentCompany($materialRequest);

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'partially_approved', 'rejected', 'purchase_required', 'cancelled'])],
            'material_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'project_task_id' => ['nullable', 'integer'],
            'approved_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->ensureProjectTaskLink($data['project_id'] ?? null, $data['project_task_id'] ?? null);

        $materialId = $this->resolvedRequestMaterialId($materialRequest, $data['material_id'] ?? null);
        $approvedQuantity = (float) ($data['approved_quantity'] ?? 0);
        if (in_array($data['status'], ['approved', 'partially_approved'], true) && $approvedQuantity <= 0) {
            $approvedQuantity = (float) $materialRequest->requested_quantity;
        }

        $materialRequest->update([
            'material_id' => $materialId,
            'project_id' => $data['project_id'] ? (int) $data['project_id'] : null,
            'project_task_id' => $data['project_task_id'] ? (int) $data['project_task_id'] : null,
            'status' => $data['status'],
            'approved_quantity' => $approvedQuantity,
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Material request updated successfully.');
    }

    public function destroyRequest(int $materialRequestId): RedirectResponse
    {
        $this->ensureInventoryProjectColumns();

        if (! $this->hasTable('material_requests')) {
            return redirect()
                ->route('admin.material-requests.index')
                ->with('error', 'Material request table is missing. Please create the material_requests table first.');
        }

        $materialRequest = MaterialRequest::query()->forCurrentCompany()->findOrFail($materialRequestId);
        $this->ensureCurrentCompany($materialRequest);

        $hasIssuedMaterial = (float) $materialRequest->issued_quantity > 0;
        if (! $hasIssuedMaterial && $this->hasTable('material_issues')) {
            $hasIssuedMaterial = MaterialIssue::query()
                ->forCurrentCompany()
                ->where('material_request_id', $materialRequest->id)
                ->exists();
        }

        if ($hasIssuedMaterial) {
            return back()->with('error', 'This request already has issued material, so it cannot be deleted.');
        }

        $materialRequest->delete();

        return back()->with('success', 'Material request deleted successfully.');
    }

    public function issue(Request $request, int $materialRequestId): RedirectResponse
    {
        $this->ensureInventoryProjectColumns();

        if (! $this->hasTable('material_requests')) {
            return redirect()
                ->route('admin.material-requests.index')
                ->with('error', 'Material request table is missing. Please create the material_requests table first.');
        }

        $materialRequest = MaterialRequest::query()->forCurrentCompany()->findOrFail($materialRequestId);
        $this->ensureCurrentCompany($materialRequest);

        if (! $this->hasTable('material_issues') || ! $this->hasTable('material_stocks') || ! $this->hasTable('stock_movements')) {
            return back()->with('error', 'Issue or stock tables are missing. Please create material_issues, material_stocks, and stock_movements tables first.');
        }

        if (! $materialRequest->material_id) {
            return back()->with('error', 'This request has typed material only. Link it to a material master item before issuing stock.');
        }

        $data = $request->validate([
            'issued_quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'issue_source_labour_site_id' => ['nullable', 'integer'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $quantity = (float) $data['issued_quantity'];
        $sourceSiteId = ($data['issue_source_labour_site_id'] ?? null) ? (int) $data['issue_source_labour_site_id'] : null;
        $remainingApproved = max(0, (float) $materialRequest->approved_quantity - (float) $materialRequest->issued_quantity);

        if ($remainingApproved > 0 && $quantity > $remainingApproved) {
            return back()->with('error', 'Issue quantity cannot be more than remaining approved quantity.');
        }

        if ($quantity > $this->stockService->availableQuantity((int) $materialRequest->material_id, $sourceSiteId)) {
            return back()->with('error', 'Selected stock source does not have enough material.');
        }

        DB::transaction(function () use ($materialRequest, $quantity, $sourceSiteId, $data) {
            $issue = MaterialIssue::query()->create([
                'material_request_id' => $materialRequest->id,
                'material_id' => $materialRequest->material_id,
                'labour_site_id' => $materialRequest->labour_site_id,
                'project_id' => $materialRequest->project_id,
                'project_task_id' => $materialRequest->project_task_id,
                'issued_quantity' => $quantity,
                'issued_by' => session('admin_user_id'),
                'issued_at' => now(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->stockService->removeStock(
                (int) $materialRequest->material_id,
                $sourceSiteId,
                $quantity,
                StockMovement::ISSUE_OUT,
                MaterialIssue::class,
                $issue->id,
                $data['remarks'] ?? 'Material issued against request #'.$materialRequest->id,
                $materialRequest->project_id ? (int) $materialRequest->project_id : null,
                $materialRequest->project_task_id ? (int) $materialRequest->project_task_id : null
            );

            $materialRequest->issued_quantity = (float) $materialRequest->issued_quantity + $quantity;
            $materialRequest->status = (float) $materialRequest->issued_quantity >= (float) $materialRequest->approved_quantity
                ? 'issued'
                : 'partially_approved';
            $materialRequest->save();
        });

        return back()->with('success', 'Material issued and stock deducted successfully.');
    }

    public function issues(): View
    {
        $this->ensureInventoryProjectColumns();

        if (! $this->hasTable('material_issues') || ! $this->hasTable('stock_movements')) {
            return view('admin.material-stock.issues', [
                'issues' => $this->emptyPaginator(),
                'movements' => collect(),
            ])->with('error', 'Material issue or stock movement table is missing. Please create the material stock tables first.');
        }

        return view('admin.material-stock.issues', [
            'issues' => MaterialIssue::query()
                ->forCurrentCompany()
                ->with(['request.engineer:id,name,mobile', 'material:id,name,unit', 'site:id,name', 'project:id,name,code', 'task:id,title', 'issuer:id,name'])
                ->latest('issued_at')
                ->paginate(20),
            'movements' => StockMovement::query()
                ->forCurrentCompany()
                ->with(['material:id,name,unit', 'site:id,name', 'project:id,name,code', 'task:id,title'])
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    private function activeMaterials()
    {
        if (! $this->hasTable('materials')) {
            return collect();
        }

        return Material::query()->forCurrentCompany()->where('is_active', true)->orderBy('name')->get();
    }

    private function activeSites()
    {
        return LabourSite::query()->forCurrentCompany()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    private function activeProjects()
    {
        return Project::query()
            ->forCurrentCompany()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function projectTasks(mixed $projectId)
    {
        if (! $projectId) {
            return collect();
        }

        return ProjectTask::query()
            ->forCurrentCompany()
            ->where('project_id', $projectId)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'project_id', 'title', 'boq_item_number']);
    }

    private function totalAvailableQuantity(int $materialId): float
    {
        if (! $this->hasTable('material_stocks')) {
            return 0.0;
        }

        return (float) MaterialStock::query()
            ->forCurrentCompany()
            ->where('material_id', $materialId)
            ->sum('available_quantity');
    }

    private function stockRowsForMaterial(int $materialId)
    {
        if (! $this->hasTable('material_stocks')) {
            return collect();
        }

        return MaterialStock::query()
            ->forCurrentCompany()
            ->with('site:id,name')
            ->where('material_id', $materialId)
            ->where('available_quantity', '>', 0)
            ->orderByRaw('labour_site_id IS NOT NULL')
            ->orderBy('labour_site_id')
            ->get();
    }

    private function ensureCurrentCompany(Material|MaterialRequest $record): void
    {
        $companyId = app(\App\Support\Tenant::class)->id();

        if ($companyId && (int) $record->company_id !== (int) $companyId) {
            abort(404);
        }
    }

    private function emptyPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(collect(), 0, 20, 1, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function requestRelations(): array
    {
        $relations = ['engineer:id,name,mobile,designation', 'site:id,name', 'project:id,name,code', 'task:id,title,boq_item_number'];

        if ($this->hasTable('materials')) {
            $relations[] = 'material:id,name,material_type,unit';
        }

        return $relations;
    }

    private function hasTable(string $table): bool
    {
        return DB::connection(app(Tenant::class)->connectionName())
            ->getSchemaBuilder()
            ->hasTable($table);
    }

    private function ensureInventoryProjectColumns(): void
    {
        $connection = app(Tenant::class)->connectionName() ?: config('database.default');
        $schema = Schema::connection($connection);

        foreach (['material_requests', 'material_issues', 'stock_movements'] as $tableName) {
            if (! $schema->hasTable($tableName)) {
                continue;
            }

            if (! $schema->hasColumn($tableName, 'project_id')) {
                $schema->table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('project_id')->nullable()->after('labour_site_id');
                });
            }

            if (! $schema->hasColumn($tableName, 'project_task_id')) {
                $schema->table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('project_task_id')->nullable()->after('project_id');
                });
            }
        }
    }

    private function resolvedRequestMaterialId(MaterialRequest $materialRequest, mixed $selectedMaterialId): ?int
    {
        if (! $this->hasTable('materials')) {
            return $materialRequest->material_id;
        }

        if ($selectedMaterialId && Material::query()->forCurrentCompany()->whereKey($selectedMaterialId)->exists()) {
            return (int) $selectedMaterialId;
        }

        if ($materialRequest->material_id) {
            return (int) $materialRequest->material_id;
        }

        $materialName = trim((string) $materialRequest->material_name);

        if ($materialName === '') {
            return null;
        }

        $material = Material::query()
            ->forCurrentCompany()
            ->whereRaw('LOWER(name) = ?', [strtolower($materialName)])
            ->first();

        if ($material) {
            if (! $material->unit && $materialRequest->unit) {
                $material->update(['unit' => $materialRequest->unit]);
            }

            return (int) $material->id;
        }

        return (int) Material::query()->create([
            'name' => $materialName,
            'unit' => $materialRequest->unit,
            'is_active' => true,
        ])->id;
    }

    private function ensureProjectTaskLink(mixed $projectId, mixed $projectTaskId): void
    {
        if ($projectId && ! Project::query()->forCurrentCompany()->whereKey($projectId)->exists()) {
            abort(422, 'Selected project was not found.');
        }

        if (! $projectTaskId) {
            return;
        }

        $taskQuery = ProjectTask::query()->forCurrentCompany()->whereKey($projectTaskId);

        if ($projectId) {
            $taskQuery->where('project_id', $projectId);
        }

        if (! $taskQuery->exists()) {
            abort(422, 'Selected project task was not found.');
        }
    }
}
