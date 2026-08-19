<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Support\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureSupplierStatutoryColumns();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $search = $filters['q'] ?? ($filters['search'] ?? null);

        $suppliers = Supplier::query()
            ->forCurrentCompany()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%')
                        ->orWhere('gstin', 'like', '%'.$search.'%');
                });
            })
            ->when(array_key_exists('active', $filters), fn ($query) => $query->where('is_active', $filters['active']))
            ->orderBy('name')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->map(fn (Supplier $supplier) => $this->supplierPayload($supplier));

        return response()->json([
            'message' => 'Suppliers fetched successfully.',
            'suppliers' => $suppliers,
        ]);
    }

    public function show(int $supplier): JsonResponse
    {
        $this->ensureSupplierStatutoryColumns();

        $supplier = Supplier::query()
            ->forCurrentCompany()
            ->findOrFail($supplier);

        return response()->json([
            'message' => 'Supplier fetched successfully.',
            'supplier' => $this->supplierPayload($supplier),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureSupplierStatutoryColumns();

        $data = $this->validatedData($request);
        $data['is_active'] = $data['is_active'] ?? true;

        $supplier = Supplier::query()->create($data);

        return response()->json([
            'message' => 'Supplier created successfully.',
            'supplier' => $this->supplierPayload($supplier),
        ], 201);
    }

    public function update(Request $request, int $supplier): JsonResponse
    {
        $this->ensureSupplierStatutoryColumns();

        $supplier = Supplier::query()
            ->forCurrentCompany()
            ->findOrFail($supplier);

        $supplier->update($this->validatedData($request, $supplier->id));

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'supplier' => $this->supplierPayload($supplier->refresh()),
        ]);
    }

    private function supplierPayload(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'label' => $supplier->name,
            'value' => $supplier->id,
            'contact_person' => $supplier->contact_person,
            'mobile' => $supplier->mobile,
            'email' => $supplier->email,
            'gstin' => $supplier->gstin,
            'gst_registration_type' => $supplier->gst_registration_type,
            'gst_return_status' => $supplier->gst_return_status,
            'tds_section' => $supplier->tds_section,
            'tds_percent' => $supplier->tds_percent,
            'e_invoice_applicable' => $supplier->e_invoice_applicable,
            'e_way_bill_applicable' => $supplier->e_way_bill_applicable,
            'vendor_reconciliation_status' => $supplier->vendor_reconciliation_status,
            'auditor_export_note' => $supplier->auditor_export_note,
            'address' => $supplier->address,
            'default_dispatched_through' => $supplier->default_dispatched_through,
            'default_destination' => $supplier->default_destination,
            'default_terms' => $supplier->default_terms,
            'is_active' => $supplier->is_active,
            'created_at' => $supplier->created_at,
            'updated_at' => $supplier->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $companyId = app(Tenant::class)->id();

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($this->tenantTable('suppliers'), 'name')
                    ->where('company_id', $companyId)
                    ->ignore($ignoreId),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:40'],
            'gst_registration_type' => ['nullable', 'string', 'max:80'],
            'gst_return_status' => ['nullable', 'string', 'max:80'],
            'tds_section' => ['nullable', 'string', 'max:80'],
            'tds_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'e_invoice_applicable' => ['nullable', 'boolean'],
            'e_way_bill_applicable' => ['nullable', 'boolean'],
            'vendor_reconciliation_status' => ['nullable', 'string', 'max:80'],
            'auditor_export_note' => ['nullable', 'string', 'max:3000'],
            'address' => ['nullable', 'string', 'max:2000'],
            'default_dispatched_through' => ['nullable', 'string', 'max:120'],
            'default_destination' => ['nullable', 'string', 'max:160'],
            'default_terms' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function tenantTable(string $table): string
    {
        $connectionName = app(Tenant::class)->connectionName();

        return $connectionName ? $connectionName.'.'.$table : $table;
    }

    private function ensureSupplierStatutoryColumns(): void
    {
        $connection = app(Tenant::class)->connectionName() ?: config('database.default');
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('suppliers')) {
            return;
            
        }

        $this->ensureColumn($schema, 'gst_registration_type', fn (Blueprint $table) => $table->string('gst_registration_type', 80)->nullable()->after('gstin'));
        $this->ensureColumn($schema, 'gst_return_status', fn (Blueprint $table) => $table->string('gst_return_status', 80)->nullable()->after('gst_registration_type'));
        $this->ensureColumn($schema, 'tds_section', fn (Blueprint $table) => $table->string('tds_section', 80)->nullable()->after('gst_return_status'));
        $this->ensureColumn($schema, 'tds_percent', fn (Blueprint $table) => $table->decimal('tds_percent', 5, 2)->nullable()->after('tds_section'));
        $this->ensureColumn($schema, 'e_invoice_applicable', fn (Blueprint $table) => $table->boolean('e_invoice_applicable')->default(false)->after('tds_percent'));
        $this->ensureColumn($schema, 'e_way_bill_applicable', fn (Blueprint $table) => $table->boolean('e_way_bill_applicable')->default(false)->after('e_invoice_applicable'));
        $this->ensureColumn($schema, 'vendor_reconciliation_status', fn (Blueprint $table) => $table->string('vendor_reconciliation_status', 80)->nullable()->after('e_way_bill_applicable'));
        $this->ensureColumn($schema, 'auditor_export_note', fn (Blueprint $table) => $table->text('auditor_export_note')->nullable()->after('vendor_reconciliation_status'));
    }

    private function ensureColumn($schema, string $column, callable $definition): void
    {
        if (! $schema->hasColumn('suppliers', $column)) {
            $schema->table('suppliers', $definition);
        }
    }
}
