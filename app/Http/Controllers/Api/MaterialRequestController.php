<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialStock;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialRequestController extends Controller
{
    public function materials(): JsonResponse
    {
        if (! $this->hasTable('materials')) {
            return response()->json([
                'message' => 'Materials table is not available yet.',
                'materials' => [],
            ]);
        }

        $materials = Material::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Material $material) => $this->materialPayload($material));

        return response()->json([
            'message' => 'Materials fetched successfully.',
            'materials' => $materials,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        if (! $this->hasTable('material_requests')) {
            return response()->json([
                'message' => 'Material request table is not available yet.',
                'material_requests' => [],
            ], 503);
        }

        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $requests = MaterialRequest::query()
            ->forCurrentCompany()
            ->with($this->requestRelations())
            ->where('user_id', $request->user()->id)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn (MaterialRequest $materialRequest) => $this->requestPayload($materialRequest));

        return response()->json([
            'message' => 'Material requests fetched successfully.',
            'material_requests' => $requests,
        ]);
    }

    public function allMaterialRequests(Request $request): JsonResponse
    {
        if (! $this->hasTable('material_requests')) {
            return response()->json([
                'message' => 'Material request table is not available yet.',
                'material_requests' => [],
            ], 503);
        }

        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'labour_site_id' => ['nullable', 'integer'],
            'material_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $baseQuery = MaterialRequest::query()->forCurrentCompany();
        $filteredQuery = (clone $baseQuery)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['labour_site_id']), fn ($query) => $query->where('labour_site_id', $filters['labour_site_id']))
            ->when(isset($filters['material_id']), fn ($query) => $query->where('material_id', $filters['material_id']))
            ->when(isset($filters['project_id']), fn ($query) => $query->where('project_id', $filters['project_id']))
            ->when(isset($filters['from_date']), fn ($query) => $query->whereDate('request_date', '>=', $filters['from_date']))
            ->when(isset($filters['to_date']), fn ($query) => $query->whereDate('request_date', '<=', $filters['to_date']))
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query->where('material_name', 'like', "%{$search}%")
                        ->orWhere('site_project', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%");
                });
            });

        $requests = (clone $filteredQuery)
            ->forCurrentCompany()
            ->with($this->requestRelations(includeEngineer: true, includeProject: true))
            ->latest('request_date')
            ->latest('id')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->map(fn (MaterialRequest $materialRequest) => $this->requestPayload($materialRequest, includeAvailability: true));

        return response()->json([
            'message' => 'All material requests fetched successfully.',
            'summary' => [
                'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                'purchase_required' => (clone $baseQuery)->where('status', 'purchase_required')->count(),
                'issued' => (clone $baseQuery)->where('status', 'issued')->count(),
                'current_page' => $requests->count(),
                'total_filtered' => (clone $filteredQuery)->count(),
            ],
            'material_requests' => $requests,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->hasTable('material_requests')) {
            return response()->json([
                'message' => 'Material request table is not available yet.',
            ], 503);
        }

        $this->normalizeStoreInput($request);

        $data = $request->validate([
            'request_date' => ['nullable', 'date'],
            'required_by' => ['nullable', 'date'],
            'labour_site_id' => ['nullable', 'integer'],
            'site_project' => ['nullable', 'string', 'max:255'],
            'material_id' => ['nullable', 'integer'],
            'material_name' => ['required_without:material_id', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'requested_quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'required_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:30'],
            'purpose' => ['nullable', 'string', 'max:2000'],
        ]);

        $material = $this->resolveMaterial($data);
        $site = $this->resolveSite($data);

        $materialRequest = MaterialRequest::query()->create([
            'user_id' => $request->user()->id,
            'labour_site_id' => $site?->id,
            'material_id' => $material?->id,
            'request_date' => $data['request_date'] ?? now()->toDateString(),
            'required_by' => $data['required_by'] ?? ($data['required_date'] ?? null),
            'site_project' => $data['site_project'] ?? $site?->name,
            'material_name' => $data['material_name'] ?? $material?->name,
            'unit' => $data['unit'] ?? $material?->unit,
            'requested_quantity' => $data['requested_quantity'],
            'required_date' => $data['required_date'] ?? ($data['required_by'] ?? null),
            'priority' => $data['priority'] ?? 'normal',
            'purpose' => $data['purpose'] ?? null,
            'status' => 'pending',
        ]);

        $materialRequest->load($this->requestRelations());

        return response()->json([
            'message' => 'Material request submitted successfully.',
            'material_request' => $this->requestPayload($materialRequest),
        ], 201);
    }

    public function show(Request $request, int $materialRequest): JsonResponse
    {
        if (! $this->hasTable('material_requests')) {
            return response()->json([
                'message' => 'Material request table is not available yet.',
            ], 503);
        }

        $materialRequest = MaterialRequest::query()
            ->forCurrentCompany()
            ->with($this->requestRelations(includeIssues: true))
            ->where('user_id', $request->user()->id)
            ->findOrFail($materialRequest);

        return response()->json([
            'message' => 'Material request fetched successfully.',
            'material_request' => $this->requestPayload($materialRequest),
        ]);
    }

    public function sites(): JsonResponse
    {
        return response()->json([
            'message' => 'Sites fetched successfully.',
            'sites' => LabourSite::query()
                ->forCurrentCompany()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'address'])
                ->map(fn (LabourSite $site) => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'label' => $site->name,
                    'value' => $site->id,
                    'address' => $site->address,
                ]),
        ]);
    }

    private function materialPayload(Material $material): array
    {
        return [
            'id' => $material->id,
            'name' => $material->name,
            'material_type' => $material->material_type,
            'unit' => $material->unit,
            'minimum_stock' => $material->minimum_stock,
        ];
    }

    private function requestPayload(MaterialRequest $materialRequest, bool $includeAvailability = false): array
    {
        $material = $materialRequest->relationLoaded('material') ? $materialRequest->material : null;
        $site = $materialRequest->relationLoaded('site') ? $materialRequest->site : null;
        $engineer = $materialRequest->relationLoaded('engineer') ? $materialRequest->engineer : null;
        $project = $materialRequest->relationLoaded('project') ? $materialRequest->project : null;
        $task = $materialRequest->relationLoaded('task') ? $materialRequest->task : null;
        $stockRows = $includeAvailability ? $this->stockRowsForMaterial($materialRequest->material_id ? (int) $materialRequest->material_id : null) : collect();
        $availableQuantity = $stockRows->sum(fn (MaterialStock $stock) => (float) $stock->available_quantity);
        $remainingApproved = max(0, (float) $materialRequest->approved_quantity - (float) $materialRequest->issued_quantity);

        $payload = [
            'id' => $materialRequest->id,
            'user' => $engineer ? [
                'id' => $engineer->id,
                'name' => $engineer->name,
                'mobile' => $engineer->mobile,
            ] : null,
            'requested_by' => $engineer?->name,
            'site' => $site ? [
                'id' => $site->id,
                'name' => $site->name,
            ] : null,
            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code ?? null,
            ] : null,
            'task' => $task ? [
                'id' => $task->id,
                'title' => $task->title,
                'boq_item_number' => $task->boq_item_number ?? null,
            ] : null,
            'material' => $material ? $this->materialPayload($material) : null,
            'request_date' => $materialRequest->request_date?->toDateString(),
            'required_by' => $materialRequest->required_by?->toDateString(),
            'site_project' => $materialRequest->site_project,
            'material_name' => $materialRequest->material_name ?: $material?->name,
            'requested_quantity' => $materialRequest->requested_quantity,
            'quantity' => $materialRequest->requested_quantity,
            'unit' => $materialRequest->unit ?: $material?->unit,
            'approved_quantity' => $materialRequest->approved_quantity,
            'issued_quantity' => $materialRequest->issued_quantity,
            'required_date' => $materialRequest->required_date?->toDateString(),
            'priority' => $materialRequest->priority ?? 'normal',
            'purpose' => $materialRequest->purpose,
            'remarks' => $materialRequest->purpose,
            'status' => $materialRequest->status,
            'admin_note' => $materialRequest->admin_note,
            'submitted_at' => $materialRequest->created_at,
            'updated_at' => $materialRequest->updated_at,
        ];

        if ($includeAvailability) {
            $payload['available_quantity'] = number_format($availableQuantity, 2, '.', '');
            $payload['remaining_approved_quantity'] = number_format($remainingApproved, 2, '.', '');
            $payload['available_by_store'] = $stockRows
                ->map(fn (MaterialStock $stock) => [
                    'labour_site_id' => $stock->labour_site_id,
                    'site_name' => $stock->site?->name ?? 'Main Store',
                    'available_quantity' => $stock->available_quantity,
                ])
                ->values();
        }

        return $payload;
    }

    private function normalizeStoreInput(Request $request): void
    {
        $data = [];

        if (! $request->has('request_date')) {
            foreach (['requestDate', 'date'] as $key) {
                if ($request->has($key)) {
                    $data['request_date'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('required_by')) {
            foreach (['requiredBy', 'required_date', 'requiredDate'] as $key) {
                if ($request->has($key)) {
                    $data['required_by'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('site_project')) {
            foreach (['siteProject', 'site_project', 'site_name', 'site', 'project'] as $key) {
                if ($request->has($key)) {
                    $data['site_project'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('material_name')) {
            foreach (['materialName', 'material', 'product_name', 'productName'] as $key) {
                if ($request->has($key)) {
                    $data['material_name'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('requested_quantity')) {
            foreach (['quantity', 'qty', 'requestedQuantity'] as $key) {
                if ($request->has($key)) {
                    $data['requested_quantity'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('purpose')) {
            foreach (['remarks', 'remark', 'purpose_remarks', 'purposeRemarks'] as $key) {
                if ($request->has($key)) {
                    $data['purpose'] = $request->input($key);
                    break;
                }
            }
        }

        if ($data) {
            $request->merge($data);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveMaterial(array $data): ?Material
    {
        if (! $this->hasTable('materials')) {
            return null;
        }

        if (! empty($data['material_id'])) {
            $material = Material::query()->forCurrentCompany()->find($data['material_id']);

            if ($material) {
                return $material;
            }
        }

        $materialName = trim((string) $data['material_name']);
        $unit = $data['unit'] ?? null;

        $material = Material::query()
            ->forCurrentCompany()
            ->whereRaw('LOWER(name) = ?', [strtolower($materialName)])
            ->first();

        if ($material) {
            if (! $material->unit && $unit) {
                $material->update(['unit' => $unit]);
            }

            return $material;
        }

        return Material::query()->create([
            'name' => $materialName,
            'unit' => $unit,
            'is_active' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveSite(array $data): ?LabourSite
    {
        if (! empty($data['labour_site_id'])) {
            $site = LabourSite::query()
                ->forCurrentCompany()
                ->where('id', $data['labour_site_id'])
                ->first();

            if ($site) {
                return $site;
            }
        }

        $siteProject = trim((string) ($data['site_project'] ?? ''));

        if ($siteProject === '') {
            return null;
        }

        return LabourSite::query()
            ->forCurrentCompany()
            ->whereRaw('LOWER(name) = ?', [strtolower($siteProject)])
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function requestRelations(bool $includeIssues = false, bool $includeEngineer = false, bool $includeProject = false): array
    {
        $relations = ['site'];

        if ($this->hasTable('materials')) {
            $relations[] = 'material';
        }

        if ($includeEngineer) {
            $relations[] = 'engineer:id,name,mobile';
        }

        if ($includeProject) {
            $relations[] = 'project:id,name,code';
            $relations[] = 'task:id,title,boq_item_number';
        }

        if ($includeIssues) {
            $relations[] = 'issues';
        }

        return $relations;
    }

    private function hasTable(string $table): bool
    {
        return DB::connection(app(Tenant::class)->connectionName())
            ->getSchemaBuilder()
            ->hasTable($table);
    }

    private function stockRowsForMaterial(?int $materialId): \Illuminate\Support\Collection
    {
        if (! $materialId || ! $this->hasTable('material_stocks')) {
            return collect();
        }

        return MaterialStock::query()
            ->forCurrentCompany()
            ->with('site:id,name')
            ->where('material_id', $materialId)
            ->where('available_quantity', '>', 0)
            ->orderByDesc('available_quantity')
            ->get();
    }
}
