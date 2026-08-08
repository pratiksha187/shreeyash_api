<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\SafetyItem;
use App\Models\SafetyRequest;
use App\Support\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SafetyRequestController extends Controller
{
    public function materials(): JsonResponse
    {
        $this->ensureSafetyTables();

        $items = SafetyItem::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (SafetyItem $item) => $this->itemPayload($item));

        return response()->json([
            'message' => 'Safety materials fetched successfully.',
            'safety_materials' => $items,
            'materials' => $items,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureSafetyTables();

        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $requests = SafetyRequest::query()
            ->forCurrentCompany()
            ->with(['item', 'site', 'project:id,name,code', 'task:id,title'])
            ->where('user_id', $request->user()->id)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn (SafetyRequest $safetyRequest) => $this->requestPayload($safetyRequest));

        return response()->json([
            'message' => 'Safety requests fetched successfully.',
            'safety_requests' => $requests,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureSafetyTables();
        $this->normalizeStoreInput($request);

        $data = $request->validate([
            'request_date' => ['nullable', 'date'],
            'required_by' => ['nullable', 'date'],
            'labour_site_id' => ['nullable', 'integer'],
            'site_project' => ['nullable', 'string', 'max:255'],
            'safety_item_id' => ['nullable', 'integer'],
            'material_id' => ['nullable', 'integer'],
            'material_name' => ['required_without_all:safety_item_id,material_id', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'requested_quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'priority' => ['nullable', 'string', 'max:30'],
            'purpose' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = $this->resolveItem($data);
        $site = $this->resolveSite($data);

        $safetyRequest = SafetyRequest::query()->create([
            'user_id' => $request->user()->id,
            'safety_item_id' => $item->id,
            'labour_site_id' => $site?->id,
            'request_date' => $data['request_date'] ?? now()->toDateString(),
            'required_by' => $data['required_by'] ?? null,
            'requested_quantity' => $data['requested_quantity'],
            'requested_by' => $request->user()->name,
            'priority' => $data['priority'] ?? 'normal',
            'purpose' => $data['purpose'] ?? null,
            'status' => 'pending',
        ]);

        $safetyRequest->load(['item', 'site', 'project:id,name,code', 'task:id,title']);

        return response()->json([
            'message' => 'Safety request submitted successfully.',
            'safety_request' => $this->requestPayload($safetyRequest),
        ], 201);
    }

    public function show(Request $request, int $safetyRequest): JsonResponse
    {
        $this->ensureSafetyTables();

        $safetyRequest = SafetyRequest::query()
            ->forCurrentCompany()
            ->with(['item', 'site', 'project:id,name,code', 'task:id,title', 'issues'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($safetyRequest);

        return response()->json([
            'message' => 'Safety request fetched successfully.',
            'safety_request' => $this->requestPayload($safetyRequest),
        ]);
    }

    private function itemPayload(SafetyItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'label' => $item->name,
            'value' => $item->id,
            'category' => $item->category,
            'unit' => $item->unit,
            'minimum_stock' => $item->minimum_stock,
        ];
    }

    private function requestPayload(SafetyRequest $safetyRequest): array
    {
        $item = $safetyRequest->relationLoaded('item') ? $safetyRequest->item : null;
        $site = $safetyRequest->relationLoaded('site') ? $safetyRequest->site : null;

        return [
            'id' => $safetyRequest->id,
            'site' => $site ? ['id' => $site->id, 'name' => $site->name] : null,
            'material' => $item ? $this->itemPayload($item) : null,
            'safety_material' => $item ? $this->itemPayload($item) : null,
            'request_date' => $safetyRequest->request_date?->toDateString(),
            'required_by' => $safetyRequest->required_by?->toDateString(),
            'material_name' => $item?->name,
            'requested_quantity' => $safetyRequest->requested_quantity,
            'quantity' => $safetyRequest->requested_quantity,
            'unit' => $item?->unit,
            'approved_quantity' => $safetyRequest->approved_quantity,
            'issued_quantity' => $safetyRequest->issued_quantity,
            'priority' => $safetyRequest->priority ?? 'normal',
            'purpose' => $safetyRequest->purpose,
            'remarks' => $safetyRequest->purpose,
            'status' => $safetyRequest->status,
            'admin_note' => $safetyRequest->admin_note,
            'submitted_at' => $safetyRequest->created_at,
            'updated_at' => $safetyRequest->updated_at,
        ];
    }

    private function normalizeStoreInput(Request $request): void
    {
        $data = [];

        foreach (['requestDate', 'date'] as $key) {
            if (! $request->has('request_date') && $request->has($key)) {
                $data['request_date'] = $request->input($key);
            }
        }

        foreach (['requiredBy', 'required_date', 'requiredDate'] as $key) {
            if (! $request->has('required_by') && $request->has($key)) {
                $data['required_by'] = $request->input($key);
            }
        }

        foreach (['siteProject', 'site_name', 'site', 'project'] as $key) {
            if (! $request->has('site_project') && $request->has($key)) {
                $data['site_project'] = $request->input($key);
            }
        }

        foreach (['materialName', 'material', 'safetyMaterial', 'safety_material_name'] as $key) {
            if (! $request->has('material_name') && $request->has($key)) {
                $data['material_name'] = $request->input($key);
            }
        }

        foreach (['quantity', 'qty', 'requestedQuantity'] as $key) {
            if (! $request->has('requested_quantity') && $request->has($key)) {
                $data['requested_quantity'] = $request->input($key);
            }
        }

        foreach (['remarks', 'remark', 'purpose_remarks'] as $key) {
            if (! $request->has('purpose') && $request->has($key)) {
                $data['purpose'] = $request->input($key);
            }
        }

        if ($data) {
            $request->merge($data);
        }
    }

    private function resolveItem(array $data): SafetyItem
    {
        $itemId = $data['safety_item_id'] ?? ($data['material_id'] ?? null);
        if ($itemId) {
            $item = SafetyItem::query()->forCurrentCompany()->where('is_active', true)->find($itemId);
            if ($item) {
                return $item;
            }
        }

        $name = trim((string) $data['material_name']);
        $unit = $data['unit'] ?? null;

        $item = SafetyItem::query()->forCurrentCompany()->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($item) {
            if (! $item->unit && $unit) {
                $item->update(['unit' => $unit]);
            }

            return $item;
        }

        return SafetyItem::query()->create([
            'name' => $name,
            'unit' => $unit,
            'category' => 'PPE',
            'is_active' => true,
        ]);
    }

    private function resolveSite(array $data): ?LabourSite
    {
        if (! empty($data['labour_site_id'])) {
            return LabourSite::query()->forCurrentCompany()->whereKey($data['labour_site_id'])->first();
        }

        $siteProject = trim((string) ($data['site_project'] ?? ''));
        if ($siteProject === '') {
            return null;
        }

        return LabourSite::query()->forCurrentCompany()->whereRaw('LOWER(name) = ?', [strtolower($siteProject)])->first();
    }

    private function ensureSafetyTables(): void
    {
        $connection = app(Tenant::class)->connectionName() ?: config('database.default');
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('safety_items')) {
            $schema->create('safety_items', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id')->nullable(); $table->string('name'); $table->string('category')->nullable(); $table->string('unit', 50)->nullable(); $table->decimal('minimum_stock', 12, 2)->default(0); $table->boolean('is_active')->default(true); $table->timestamps(); $table->index(['company_id', 'is_active']);
            });
        }

        if (! $schema->hasTable('safety_requests')) {
            $schema->create('safety_requests', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id')->nullable(); $table->unsignedBigInteger('user_id')->nullable(); $table->unsignedBigInteger('safety_item_id'); $table->unsignedBigInteger('labour_site_id')->nullable(); $table->unsignedBigInteger('project_id')->nullable(); $table->unsignedBigInteger('project_task_id')->nullable(); $table->date('request_date')->nullable(); $table->date('required_by')->nullable(); $table->decimal('requested_quantity', 12, 2); $table->decimal('approved_quantity', 12, 2)->default(0); $table->decimal('issued_quantity', 12, 2)->default(0); $table->string('requested_by')->nullable(); $table->string('priority', 30)->default('normal'); $table->string('status', 40)->default('pending'); $table->text('purpose')->nullable(); $table->text('admin_note')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->timestamps();
            });
        } else {
            $this->ensureColumn($schema, 'safety_requests', 'user_id', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable()->after('company_id'));
            $this->ensureColumn($schema, 'safety_requests', 'required_by', fn (Blueprint $table) => $table->date('required_by')->nullable()->after('request_date'));
        }
    }

    private function ensureColumn($schema, string $tableName, string $column, callable $definition): void
    {
        if (! $schema->hasColumn($tableName, $column)) {
            $schema->table($tableName, $definition);
        }
    }
}
