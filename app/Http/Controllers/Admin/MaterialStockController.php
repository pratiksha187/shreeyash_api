<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\Material;
use App\Models\MaterialIssue;
use App\Models\MaterialRequest;
use App\Models\MaterialStock;
use App\Models\StockMovement;
use App\Services\MaterialStockService;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaterialStockController extends Controller
{
    public function __construct(private readonly MaterialStockService $stockService)
    {
    }

    public function materials(): View
    {
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'material_type' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:50'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        Material::query()->create($data);

        return back()->with('success', 'Material added successfully.');
    }

    public function updateMaterial(Request $request, Material $material): RedirectResponse
    {
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
        $filters = $request->validate([
            'material_id' => ['nullable', 'exists:materials,id'],
            'labour_site_id' => ['nullable', 'exists:labour_sites,id'],
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
            'selectedMaterialId' => $filters['material_id'] ?? null,
            'selectedSiteId' => $filters['labour_site_id'] ?? null,
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
        $data = $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'labour_site_id' => ['nullable', 'exists:labour_sites,id'],
            'type' => ['required', Rule::in([StockMovement::ADJUSTMENT_IN, StockMovement::ADJUSTMENT_OUT, StockMovement::RETURN_IN])],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['type'] === StockMovement::ADJUSTMENT_OUT) {
            $this->stockService->removeStock(
                (int) $data['material_id'],
                $data['labour_site_id'] ? (int) $data['labour_site_id'] : null,
                (float) $data['quantity'],
                StockMovement::ADJUSTMENT_OUT,
                null,
                null,
                $data['remarks'] ?? 'Manual stock adjustment'
            );
        } else {
            $this->stockService->addStock(
                (int) $data['material_id'],
                $data['labour_site_id'] ? (int) $data['labour_site_id'] : null,
                (float) $data['quantity'],
                $data['type'],
                null,
                null,
                $data['remarks'] ?? 'Manual stock adjustment'
            );
        }

        return back()->with('success', 'Stock updated successfully.');
    }

    public function requests(Request $request): View
    {
        if (! $this->hasTable('material_requests')) {
            return view('admin.material-stock.requests', [
                'requests' => $this->emptyPaginator(),
                'availableByRequest' => collect(),
                'materials' => collect(),
                'statuses' => MaterialRequest::STATUSES,
                'sites' => $this->activeSites(),
                'selectedStatus' => null,
                'selectedSiteId' => null,
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
        ]);

        $requests = MaterialRequest::query()
            ->forCurrentCompany()
            ->with($this->requestRelations())
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['labour_site_id']), fn ($query) => $query->where('labour_site_id', $filters['labour_site_id']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $availableByRequest = $requests->getCollection()->mapWithKeys(fn (MaterialRequest $request) => [
            $request->id => $request->material_id ? $this->totalAvailableQuantity((int) $request->material_id) : 0.0,
        ]);

        return view('admin.material-stock.requests', [
            'requests' => $requests,
            'availableByRequest' => $availableByRequest,
            'materials' => $this->activeMaterials(),
            'statuses' => MaterialRequest::STATUSES,
            'sites' => $this->activeSites(),
            'selectedStatus' => $filters['status'] ?? null,
            'selectedSiteId' => $filters['labour_site_id'] ?? null,
            'summary' => [
                'pending' => MaterialRequest::query()->forCurrentCompany()->where('status', 'pending')->count(),
                'purchase_required' => MaterialRequest::query()->forCurrentCompany()->where('status', 'purchase_required')->count(),
                'issued' => MaterialRequest::query()->forCurrentCompany()->where('status', 'issued')->count(),
            ],
        ]);
    }

    public function updateRequest(Request $request, int $materialRequestId): RedirectResponse
    {
        if (! $this->hasTable('material_requests')) {
            return redirect()
                ->route('admin.material-requests.index')
                ->with('error', 'Material request table is missing. Please create the material_requests table first.');
        }

        $materialRequest = MaterialRequest::query()->forCurrentCompany()->findOrFail($materialRequestId);
        $this->ensureCurrentCompany($materialRequest);

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'partially_approved', 'rejected', 'purchase_required', 'cancelled'])],
            'approved_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $approvedQuantity = (float) ($data['approved_quantity'] ?? 0);
        if (in_array($data['status'], ['approved', 'partially_approved'], true) && $approvedQuantity <= 0) {
            $approvedQuantity = (float) $materialRequest->requested_quantity;
        }

        $materialRequest->update([
            'status' => $data['status'],
            'approved_quantity' => $approvedQuantity,
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Material request updated successfully.');
    }

    public function issue(Request $request, int $materialRequestId): RedirectResponse
    {
        if (! $this->hasTable('material_requests')) {
            return redirect()
                ->route('admin.material-requests.index')
                ->with('error', 'Material request table is missing. Please create the material_requests table first.');
        }

        $materialRequest = MaterialRequest::query()->forCurrentCompany()->findOrFail($materialRequestId);
        $this->ensureCurrentCompany($materialRequest);

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
                $data['remarks'] ?? 'Material issued against request #'.$materialRequest->id
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
        if (! $this->hasTable('material_issues') || ! $this->hasTable('stock_movements')) {
            return view('admin.material-stock.issues', [
                'issues' => $this->emptyPaginator(),
                'movements' => collect(),
            ])->with('error', 'Material issue or stock movement table is missing. Please create the material stock tables first.');
        }

        return view('admin.material-stock.issues', [
            'issues' => MaterialIssue::query()
                ->forCurrentCompany()
                ->with(['request.engineer:id,name,mobile', 'material:id,name,unit', 'site:id,name', 'issuer:id,name'])
                ->latest('issued_at')
                ->paginate(20),
            'movements' => StockMovement::query()
                ->forCurrentCompany()
                ->with(['material:id,name,unit', 'site:id,name'])
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
        $relations = ['engineer:id,name,mobile,designation', 'site:id,name'];

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
}
