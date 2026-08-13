<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\PurchaseWorkflow;
use App\Models\Supplier;
use App\Support\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class VendorComparisonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensurePurchaseWorkflowTable();

        $filters = $request->validate([
            'material_name' => ['nullable', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:40'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $materialName = $filters['material_name'] ?? ($filters['product_name'] ?? null);

        $comparisons = PurchaseWorkflow::query()
            ->forCurrentCompany()
            ->when($materialName, fn ($query) => $query->where('material_name', 'like', '%'.$materialName.'%'))
            ->when(isset($filters['status']), fn ($query) => $query->where('approval_status', $filters['status']))
            ->latest('id')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->map(fn (PurchaseWorkflow $workflow) => $this->comparisonPayload($workflow));

        return response()->json([
            'message' => 'Vendor comparisons fetched successfully.',
            'vendor_comparisons' => $comparisons,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensurePurchaseWorkflowTable();
        $this->normalizeInput($request);

        $data = $request->validate([
            'vendor_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'vendor_name' => ['required_without_all:vendor_id,supplier_id', 'string', 'max:255'],
            'product_name' => ['required_without:material_id', 'string', 'max:255'],
            'material_name' => ['nullable', 'string', 'max:255'],
            'material_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'status' => ['nullable', Rule::in(['pending', 'fixed', 'approved', 'rejected', 'revision'])],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $supplier = $this->resolveSupplier($data);
        $material = $this->resolveMaterial($data);
        $vendorName = $supplier?->name ?? $data['vendor_name'];
        $materialName = $material?->name ?? ($data['material_name'] ?? $data['product_name']);
        $status = $data['status'] ?? 'fixed';

        $workflow = PurchaseWorkflow::query()->create([
            'requisition_date' => now()->toDateString(),
            'material_name' => $materialName,
            'unit' => $material?->unit,
            'quantity' => 1,
            'vendor_names' => $vendorName,
            'quotation_summary' => $materialName.' | Amount '.$data['amount'],
            'selected_vendor' => in_array($status, ['fixed', 'approved'], true) ? $vendorName : null,
            'quoted_amount' => $data['amount'],
            'approval_status' => $status === 'fixed' ? 'approved' : $status,
            'po_status' => 'draft',
            'grn_status' => 'pending',
            'remarks' => $data['remarks'] ?? null,
        ]);

        return response()->json([
            'message' => 'Vendor comparison saved successfully.',
            'vendor_comparison' => $this->comparisonPayload($workflow),
        ], 201);
    }

    public function update(Request $request, int $vendorComparison): JsonResponse
    {
        $this->ensurePurchaseWorkflowTable();
        $this->normalizeInput($request);

        $workflow = PurchaseWorkflow::query()
            ->forCurrentCompany()
            ->findOrFail($vendorComparison);

        $data = $request->validate([
            'vendor_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'material_name' => ['nullable', 'string', 'max:255'],
            'material_id' => ['nullable', 'integer'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'status' => ['nullable', Rule::in(['pending', 'fixed', 'approved', 'rejected', 'revision'])],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $supplier = $this->resolveSupplier($data);
        $material = $this->resolveMaterial($data);
        $vendorName = $supplier?->name ?? ($data['vendor_name'] ?? $workflow->vendor_names);
        $materialName = $material?->name ?? ($data['material_name'] ?? ($data['product_name'] ?? $workflow->material_name));
        $status = $data['status'] ?? $workflow->approval_status;
        $amount = $data['amount'] ?? $workflow->quoted_amount;

        $workflow->update([
            'material_name' => $materialName,
            'unit' => $material?->unit ?? $workflow->unit,
            'vendor_names' => $vendorName,
            'quotation_summary' => $materialName.' | Amount '.$amount,
            'selected_vendor' => in_array($status, ['fixed', 'approved'], true) ? $vendorName : $workflow->selected_vendor,
            'quoted_amount' => $amount,
            'approval_status' => $status === 'fixed' ? 'approved' : $status,
            'remarks' => $data['remarks'] ?? $workflow->remarks,
        ]);

        return response()->json([
            'message' => 'Vendor comparison updated successfully.',
            'vendor_comparison' => $this->comparisonPayload($workflow->refresh()),
        ]);
    }

    private function comparisonPayload(PurchaseWorkflow $workflow): array
    {
        return [
            'id' => $workflow->id,
            'vendor_name' => $workflow->selected_vendor ?: $workflow->vendor_names,
            'vendor_names' => $workflow->vendor_names,
            'product_name' => $workflow->material_name,
            'material_name' => $workflow->material_name,
            'amount' => $workflow->quoted_amount,
            'status' => $workflow->approval_status === 'approved' && $workflow->selected_vendor ? 'fixed' : $workflow->approval_status,
            'approval_status' => $workflow->approval_status,
            'selected_vendor' => $workflow->selected_vendor,
            'quotation_summary' => $workflow->quotation_summary,
            'remarks' => $workflow->remarks,
            'created_at' => $workflow->created_at,
            'updated_at' => $workflow->updated_at,
        ];
    }

    private function normalizeInput(Request $request): void
    {
        $aliases = [
            'vendor_id' => ['supplier_id', 'supplierId', 'vendorId'],
            'vendor_name' => ['supplier_name', 'supplierName', 'vendorName'],
            'product_name' => ['material_name', 'materialName', 'productName', 'item_name', 'itemName'],
            'amount' => ['quoted_amount', 'quotedAmount', 'rate'],
            'status' => ['approval_status', 'approvalStatus'],
            'remarks' => ['remark', 'notes'],
        ];

        $data = [];
        foreach ($aliases as $target => $keys) {
            if ($request->has($target)) {
                continue;
            }

            foreach ($keys as $key) {
                if ($request->has($key)) {
                    $data[$target] = $request->input($key);
                    break;
                }
            }
        }

        if ($data) {
            $request->merge($data);
        }
    }

    private function resolveSupplier(array $data): ?Supplier
    {
        if (! $this->hasTable('suppliers')) {
            return null;
        }

        $supplierId = $data['vendor_id'] ?? ($data['supplier_id'] ?? null);
        if ($supplierId) {
            return Supplier::query()->forCurrentCompany()->find($supplierId);
        }

        return null;
    }

    private function resolveMaterial(array $data): ?Material
    {
        if (! $this->hasTable('materials') || empty($data['material_id'])) {
            return null;
        }

        return Material::query()->forCurrentCompany()->find($data['material_id']);
    }

    private function ensurePurchaseWorkflowTable(): void
    {
        $connection = app(Tenant::class)->connectionName() ?: config('database.default');
        $schema = Schema::connection($connection);

        if ($schema->hasTable('purchase_workflows')) {
            return;
        }

        $schema->create('purchase_workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('requisition_no', 80)->nullable();
            $table->date('requisition_date')->nullable();
            $table->string('indent_no', 80)->nullable();
            $table->string('material_name');
            $table->string('unit', 40)->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->string('vendor_enquiry_no', 80)->nullable();
            $table->text('vendor_names')->nullable();
            $table->text('quotation_summary')->nullable();
            $table->string('selected_vendor')->nullable();
            $table->decimal('quoted_amount', 15, 2)->default(0);
            $table->decimal('approval_limit', 15, 2)->default(0);
            $table->string('approval_status', 40)->default('pending');
            $table->string('po_no', 80)->nullable();
            $table->date('po_date')->nullable();
            $table->string('po_status', 40)->default('draft');
            $table->string('grn_no', 80)->nullable();
            $table->date('grn_date')->nullable();
            $table->string('grn_status', 40)->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    private function hasTable(string $table): bool
    {
        $connection = app(Tenant::class)->connectionName() ?: config('database.default');

        return Schema::connection($connection)->hasTable($table);
    }
}
