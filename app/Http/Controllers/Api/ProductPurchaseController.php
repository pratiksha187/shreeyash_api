<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\ProductPurchase;
use App\Models\SafetyItem;
use App\Models\Supplier;
use App\Support\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductPurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->hasTable('product_purchases')) {
            return response()->json([
                'message' => 'Product purchases table is not available yet.',
                'product_purchases' => [],
            ], 503);
        }

        $this->ensureProductPurchaseColumns();

        $filters = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $purchases = ProductPurchase::query()
            ->forCurrentCompany()
            ->with(['material', 'safetyItem', 'stockSite'])
            ->latest('purchase_date')
            ->latest('id')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->map(fn (ProductPurchase $purchase) => $this->purchasePayload($purchase));

        return response()->json([
            'message' => 'Product purchases fetched successfully.',
            'product_purchases' => $purchases,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->hasTable('product_purchases')) {
            return response()->json([
                'message' => 'Product purchases table is not available yet.',
            ], 503);
        }

        $this->ensureProductPurchaseColumns();
        $this->normalizeInput($request);

        $data = $request->validate([
            'purchase_date' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'integer'],
            'vendor_id' => ['nullable', 'integer'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'shop_name' => ['nullable', 'string', 'max:255'],
            'item_key' => ['nullable', 'string', 'max:80'],
            'material_id' => ['nullable', 'integer'],
            'safety_item_id' => ['nullable', 'integer'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'material_name' => ['nullable', 'string', 'max:255'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'bill_no' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:100'],
            'pcs' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'unit' => ['nullable', 'string', 'max:50'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'transport_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'bill_photo' => $request->hasFile('bill_photo') ? ['nullable', 'image', 'max:10240'] : ['nullable'],
            'photo' => $request->hasFile('photo') ? ['nullable', 'image', 'max:10240'] : ['nullable'],
            'bill_photo_base64' => ['nullable', 'string'],
        ]);

        $supplier = $this->resolveSupplier($data);
        $this->applyItem($data);

        $quantity = $this->billingQuantity($data);
        $rate = (float) ($data['rate'] ?? 0);
        $taxAmount = (float) ($data['tax_amount'] ?? 0);
        $transportAmount = (float) ($data['transport_amount'] ?? 0);

        $purchase = ProductPurchase::query()->create([
            'material_id' => $data['material_id'] ?? null,
            'safety_item_id' => $data['safety_item_id'] ?? null,
            'stock_labour_site_id' => $data['stock_labour_site_id'] ?? null,
            'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
            'supplier_name' => $supplier?->name ?? ($data['supplier_name'] ?? $data['vendor_name'] ?? $data['shop_name'] ?? null),
            'invoice_no' => $data['invoice_no'] ?? ($data['bill_no'] ?? null),
            'product_name' => $data['product_name'] ?? ($data['material_name'] ?? 'Purchased Item'),
            'size' => $data['size'] ?? null,
            'pcs' => $data['pcs'] ?? 0,
            'weight_kg' => $data['weight_kg'] ?? 0,
            'unit' => $data['unit'] ?? ((float) ($data['weight_kg'] ?? 0) > 0 ? 'Kg' : 'Nos'),
            'quantity' => $quantity,
            'rate' => $rate,
            'tax_amount' => $taxAmount,
            'transport_amount' => $transportAmount,
            'total_amount' => round(($quantity * $rate) + $taxAmount + $transportAmount, 2),
            'bill_photo_path' => $this->storeBillPhoto($request),
            'remarks' => $data['remarks'] ?? null,
        ]);

        $purchase->load(['material', 'safetyItem', 'stockSite']);

        return response()->json([
            'message' => 'Product purchase uploaded successfully.',
            'product_purchase' => $this->purchasePayload($purchase),
        ], 201);
    }

    private function purchasePayload(ProductPurchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'purchase_date' => $purchase->purchase_date?->toDateString(),
            'supplier_name' => $purchase->supplier_name,
            'invoice_no' => $purchase->invoice_no,
            'product_name' => $purchase->product_name,
            'size' => $purchase->size,
            'pcs' => $purchase->pcs,
            'weight_kg' => $purchase->weight_kg,
            'unit' => $purchase->unit,
            'quantity' => $purchase->quantity,
            'rate' => $purchase->rate,
            'tax_amount' => $purchase->tax_amount,
            'transport_amount' => $purchase->transport_amount,
            'total_amount' => $purchase->total_amount,
            'bill_photo_path' => $purchase->bill_photo_path,
            'bill_photo_url' => $purchase->bill_photo_path ? Storage::disk('public')->url($purchase->bill_photo_path) : null,
            'remarks' => $purchase->remarks,
            'created_at' => $purchase->created_at,
            'updated_at' => $purchase->updated_at,
        ];
    }

    private function normalizeInput(Request $request): void
    {
        $aliases = [
            'purchase_date' => ['purchaseDate', 'date'],
            'supplier_name' => ['vendorName', 'vendor_name', 'shopName', 'shop_name', 'vendor_shop_name'],
            'supplier_id' => ['vendor_id', 'vendorId', 'shop_id', 'shopId'],
            'product_name' => ['materialName', 'material_name', 'item_name', 'itemName', 'purchased_item'],
            'invoice_no' => ['bill_no', 'billNo', 'invoiceNo'],
            'quantity' => ['qty', 'purchase_quantity'],
            'bill_photo_base64' => ['billPhotoBase64', 'photo_base64', 'image_base64'],
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

        if (! $request->hasFile('bill_photo')) {
            foreach (['photo', 'image', 'billPhoto', 'bill_image', 'billImage'] as $key) {
                if ($request->hasFile($key)) {
                    $request->files->set('bill_photo', $request->file($key));
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

        $supplierId = $data['supplier_id'] ?? ($data['vendor_id'] ?? null);
        if ($supplierId) {
            return Supplier::query()->forCurrentCompany()->where('is_active', true)->find($supplierId);
        }

        $name = trim((string) ($data['supplier_name'] ?? $data['vendor_name'] ?? $data['shop_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        return Supplier::query()
            ->forCurrentCompany()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyItem(array &$data): void
    {
        $itemKey = (string) ($data['item_key'] ?? '');

        if (str_starts_with($itemKey, 'safety:') && $this->hasTable('safety_items')) {
            $item = SafetyItem::query()->forCurrentCompany()->find((int) substr($itemKey, 7));
            if ($item) {
                $data['safety_item_id'] = $item->id;
                $data['material_id'] = null;
                $data['product_name'] = $item->name;
                $data['unit'] = $data['unit'] ?? $item->unit;
            }

            return;
        }

        $materialId = str_starts_with($itemKey, 'material:')
            ? (int) substr($itemKey, 9)
            : (int) ($data['material_id'] ?? 0);

        if ($materialId && $this->hasTable('materials')) {
            $material = Material::query()->forCurrentCompany()->find($materialId);
            if ($material) {
                $data['material_id'] = $material->id;
                $data['safety_item_id'] = null;
                $data['product_name'] = $material->name;
                $data['unit'] = $data['unit'] ?? $material->unit;
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function billingQuantity(array $data): float
    {
        if ((float) ($data['weight_kg'] ?? 0) > 0) {
            return (float) $data['weight_kg'];
        }

        if ((float) ($data['pcs'] ?? 0) > 0) {
            return (float) $data['pcs'];
        }

        return (float) ($data['quantity'] ?? 0);
    }

    private function storeBillPhoto(Request $request): ?string
    {
        $photo = $request->file('bill_photo');

        if ($photo instanceof UploadedFile && $photo->isValid()) {
            return $photo->store('product-purchases/'.now()->format('Y/m'), 'public');
        }

        $base64 = $request->input('bill_photo_base64');
        if (! is_string($base64) || trim($base64) === '') {
            return null;
        }

        if (str_contains($base64, ',')) {
            [, $base64] = explode(',', $base64, 2);
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return null;
        }

        $path = 'product-purchases/'.now()->format('Y/m').'/bill-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.jpg';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function ensureProductPurchaseColumns(): void
    {
        $connection = app(Tenant::class)->connectionName() ?: config('database.default');
        $schema = Schema::connection($connection);

        $this->ensureColumn($schema, 'bill_photo_path', fn (Blueprint $table) => $table->string('bill_photo_path')->nullable()->after('total_amount'));
    }

    private function ensureColumn($schema, string $column, callable $definition): void
    {
        if ($schema->hasTable('product_purchases') && ! $schema->hasColumn('product_purchases', $column)) {
            $schema->table('product_purchases', $definition);
        }
    }

    private function hasTable(string $table): bool
    {
        $connection = app(Tenant::class)->connectionName() ?: config('database.default');

        return Schema::connection($connection)->hasTable($table);
    }
}
