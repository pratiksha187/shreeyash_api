<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\Material;
use App\Models\ProductPurchase;
use App\Models\StockMovement;
use App\Services\MaterialStockService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductPurchaseController extends Controller
{
    public function __construct(private readonly MaterialStockService $stockService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'search' => ['nullable', 'string', 'max:100'],
            'stock_labour_site_id' => ['nullable', 'integer'],
            'show_all' => ['nullable', 'boolean'],
        ]);

        $showAll = (bool) ($filters['show_all'] ?? false);
        $selectedMonth = $filters['month'] ?? now()->format('Y-m');
        $monthStart = $showAll ? null : Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $monthEnd = $monthStart?->copy()->endOfMonth();
        $search = $filters['search'] ?? null;
        $selectedSiteId = $filters['stock_labour_site_id'] ?? null;

        $purchases = ProductPurchase::query()
            ->forCurrentCompany()
            ->with(['material', 'stockSite'])
            ->when(! $showAll, fn ($query) => $query->whereBetween('purchase_date', [$monthStart->toDateString(), $monthEnd->toDateString()]))
            ->when($selectedSiteId, fn ($query) => $query->where('stock_labour_site_id', $selectedSiteId))
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('product_name', 'like', "%{$search}%")
                        ->orWhere('supplier_name', 'like', "%{$search}%")
                        ->orWhere('invoice_no', 'like', "%{$search}%")
                        ->orWhere('size', 'like', "%{$search}%");
                });
            })
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get();

        return view('admin.product-purchases.index', [
            'selectedMonth' => $selectedMonth,
            'monthLabel' => $showAll ? 'All' : $monthStart->format('M Y'),
            'showAll' => $showAll,
            'search' => $search,
            'selectedSiteId' => $selectedSiteId,
            'purchases' => $purchases,
            'materials' => $this->hasTable('materials')
                ? Material::query()->forCurrentCompany()->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'sites' => LabourSite::query()->forCurrentCompany()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'summary' => [
                'quantity' => $purchases->sum(fn (ProductPurchase $purchase) => (float) $purchase->quantity),
                'pcs' => $purchases->sum(fn (ProductPurchase $purchase) => (float) $purchase->pcs),
                'weight_kg' => $purchases->sum(fn (ProductPurchase $purchase) => (float) $purchase->weight_kg),
                'amount' => $purchases->sum(fn (ProductPurchase $purchase) => (float) $purchase->total_amount),
                'products' => $purchases->pluck('product_name')->filter()->unique()->count(),
                'suppliers' => $purchases->pluck('supplier_name')->filter()->unique()->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['total_amount'] = $this->totalAmount($data);

        $purchase = ProductPurchase::query()->create($data);
        $this->recordPurchaseStock($purchase);

        return redirect()
            ->route('admin.product-purchases.index', ['month' => Carbon::parse($data['purchase_date'])->format('Y-m')])
            ->with('success', 'Product purchase saved successfully.');
    }

    public function update(Request $request, int $productPurchase): RedirectResponse
    {
        $productPurchase = $this->findCurrentCompanyPurchase($productPurchase);
        $data = $this->validatedData($request);
        $data['total_amount'] = $this->totalAmount($data);

        $productPurchase->update($data);

        return redirect()
            ->route('admin.product-purchases.index', ['month' => Carbon::parse($data['purchase_date'])->format('Y-m')])
            ->with('success', 'Product purchase updated successfully.');
    }

    public function destroy(int $productPurchase): RedirectResponse
    {
        $productPurchase = $this->findCurrentCompanyPurchase($productPurchase);

        $productPurchase->delete();

        return back()
            ->with('success', 'Product purchase deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'purchase_date' => ['required', 'date'],
            'material_id' => ['nullable', 'integer'],
            'stock_labour_site_id' => ['nullable', 'integer'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'product_name' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:100'],
            'pcs' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'unit' => ['nullable', 'string', 'max:50'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'rate' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'transport_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['pcs'] = $data['pcs'] ?? 0;
        $data['weight_kg'] = $data['weight_kg'] ?? 0;
        $data['quantity'] = $this->billingQuantity($data);
        $data['unit'] = $data['unit'] ?: ((float) $data['weight_kg'] > 0 ? 'Kg' : 'Nos');

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function totalAmount(array $data): float
    {
        return round(
            ($this->billingQuantity($data) * (float) $data['rate'])
            + (float) ($data['tax_amount'] ?? 0)
            + (float) ($data['transport_amount'] ?? 0),
            2
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function billingQuantity(array $data): float
    {
        $weightKg = (float) ($data['weight_kg'] ?? 0);
        $pcs = (float) ($data['pcs'] ?? 0);

        if ($weightKg > 0) {
            return $weightKg;
        }

        if ($pcs > 0) {
            return $pcs;
        }

        return (float) ($data['quantity'] ?? 0);
    }

    private function recordPurchaseStock(ProductPurchase $purchase): void
    {
        if (! $purchase->material_id || (float) $purchase->quantity <= 0) {
            return;
        }

        if (! $this->hasTable('materials') || ! $this->hasTable('material_stocks') || ! $this->hasTable('stock_movements')) {
            return;
        }

        $this->stockService->addStock(
            (int) $purchase->material_id,
            $purchase->stock_labour_site_id ? (int) $purchase->stock_labour_site_id : null,
            (float) $purchase->quantity,
            StockMovement::PURCHASE_IN,
            ProductPurchase::class,
            $purchase->id,
            'Stock added from product purchase '.$purchase->invoice_no
        );
    }

    private function findCurrentCompanyPurchase(int $productPurchase): ProductPurchase
    {
        return ProductPurchase::query()
            ->forCurrentCompany()
            ->findOrFail($productPurchase);
    }

    private function hasTable(string $table): bool
    {
        return DB::connection(app(\App\Support\Tenant::class)->connectionName())
            ->getSchemaBuilder()
            ->hasTable($table);
    }
}
