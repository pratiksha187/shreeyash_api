<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabourSite;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\SafetyIssue;
use App\Models\SafetyItem;
use App\Models\SafetyPurchase;
use App\Models\SafetyRequest;
use App\Models\SafetyStock;
use App\Models\SafetyStockMovement;
use App\Support\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SafetyStoreController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSafetyTables();

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(SafetyRequest::STATUSES)],
            'labour_site_id' => ['nullable', 'integer'],
        ]);

        $requests = SafetyRequest::query()
            ->forCurrentCompany()
            ->with(['item', 'site', 'project:id,name,code', 'task:id,title'])
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['labour_site_id']), fn ($query) => $query->where('labour_site_id', $filters['labour_site_id']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.safety-store.index', [
            'items' => $this->activeItems(),
            'allItems' => SafetyItem::query()->forCurrentCompany()->withSum('stocks', 'available_quantity')->latest()->paginate(10, ['*'], 'items_page'),
            'stocks' => SafetyStock::query()->forCurrentCompany()->with(['item', 'site'])->whereHas('item')->latest()->paginate(10, ['*'], 'stock_page'),
            'purchases' => SafetyPurchase::query()->forCurrentCompany()->with(['item', 'stockSite'])->latest('purchase_date')->limit(10)->get(),
            'requests' => $requests,
            'issues' => SafetyIssue::query()->forCurrentCompany()->with(['item', 'site', 'request', 'request.project', 'request.task'])->latest('issued_at')->limit(10)->get(),
            'movements' => SafetyStockMovement::query()->forCurrentCompany()->with(['item', 'site'])->latest()->limit(10)->get(),
            'sites' => LabourSite::query()->forCurrentCompany()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->forCurrentCompany()->orderBy('name')->get(['id', 'name', 'code']),
            'projectTasks' => ProjectTask::query()->forCurrentCompany()->orderBy('sort_order')->orderBy('title')->get(['id', 'project_id', 'title', 'boq_item_number']),
            'statuses' => SafetyRequest::STATUSES,
            'selectedStatus' => $filters['status'] ?? null,
            'selectedSiteId' => $filters['labour_site_id'] ?? null,
            'summary' => [
                'items' => SafetyItem::query()->forCurrentCompany()->count(),
                'stock_rows' => SafetyStock::query()->forCurrentCompany()->count(),
                'pending' => SafetyRequest::query()->forCurrentCompany()->where('status', 'pending')->count(),
                'issued' => SafetyRequest::query()->forCurrentCompany()->where('status', 'issued')->count(),
            ],
        ]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $this->ensureSafetyTables();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:50'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        SafetyItem::query()->create($data);

        return back()->with('success', 'Safety item added successfully.');
    }

    public function storePurchase(Request $request): RedirectResponse
    {
        $this->ensureSafetyTables();

        $data = $request->validate([
            'safety_item_id' => ['required', 'integer'],
            'stock_labour_site_id' => ['nullable', 'integer'],
            'purchase_date' => ['nullable', 'date'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'bill_no' => ['nullable', 'string', 'max:120'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = SafetyItem::query()->forCurrentCompany()->where('is_active', true)->findOrFail($data['safety_item_id']);
        $quantity = (float) $data['quantity'];
        $rate = (float) ($data['rate'] ?? 0);

        DB::transaction(function () use ($data, $item, $quantity, $rate) {
            $purchase = SafetyPurchase::query()->create([
                'safety_item_id' => $item->id,
                'stock_labour_site_id' => ($data['stock_labour_site_id'] ?? null) ? (int) $data['stock_labour_site_id'] : null,
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'supplier_name' => $data['supplier_name'] ?? null,
                'bill_no' => $data['bill_no'] ?? null,
                'quantity' => $quantity,
                'rate' => $rate,
                'total_amount' => $quantity * $rate,
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->addStock($item->id, $purchase->stock_labour_site_id ? (int) $purchase->stock_labour_site_id : null, $quantity, SafetyStockMovement::PURCHASE_IN, SafetyPurchase::class, $purchase->id, 'Safety stock added from purchase');
        });

        return back()->with('success', 'Safety purchase added and stock updated.');
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $this->ensureSafetyTables();

        $data = $request->validate([
            'safety_item_id' => ['required', 'integer'],
            'labour_site_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'project_task_id' => ['nullable', 'integer'],
            'request_date' => ['nullable', 'date'],
            'requested_quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'requested_by' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'max:30'],
            'purpose' => ['nullable', 'string', 'max:2000'],
        ]);

        SafetyItem::query()->forCurrentCompany()->findOrFail($data['safety_item_id']);

        SafetyRequest::query()->create([
            'safety_item_id' => $data['safety_item_id'],
            'labour_site_id' => ($data['labour_site_id'] ?? null) ? (int) $data['labour_site_id'] : null,
            'project_id' => ($data['project_id'] ?? null) ? (int) $data['project_id'] : null,
            'project_task_id' => ($data['project_task_id'] ?? null) ? (int) $data['project_task_id'] : null,
            'request_date' => $data['request_date'] ?? now()->toDateString(),
            'requested_quantity' => $data['requested_quantity'],
            'requested_by' => $data['requested_by'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'purpose' => $data['purpose'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Safety request added successfully.');
    }

    public function updateRequest(Request $request, int $safetyRequest): RedirectResponse
    {
        $this->ensureSafetyTables();

        $safetyRequest = SafetyRequest::query()->forCurrentCompany()->findOrFail($safetyRequest);
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'partially_approved', 'rejected', 'purchase_required', 'cancelled'])],
            'approved_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $approved = (float) ($data['approved_quantity'] ?? 0);
        if (in_array($data['status'], ['approved', 'partially_approved'], true) && $approved <= 0) {
            $approved = (float) $safetyRequest->requested_quantity;
        }

        $safetyRequest->update([
            'status' => $data['status'],
            'approved_quantity' => $approved,
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Safety request updated successfully.');
    }

    public function issue(Request $request, int $safetyRequest): RedirectResponse
    {
        $this->ensureSafetyTables();

        $safetyRequest = SafetyRequest::query()->forCurrentCompany()->findOrFail($safetyRequest);
        if (! in_array($safetyRequest->status, ['approved', 'partially_approved'], true)) {
            return back()->with('error', 'Approve this safety request before issue.');
        }

        $data = $request->validate([
            'issue_source_labour_site_id' => ['nullable', 'integer'],
            'issued_quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $quantity = (float) $data['issued_quantity'];
        $sourceSiteId = ($data['issue_source_labour_site_id'] ?? null) ? (int) $data['issue_source_labour_site_id'] : null;
        $remaining = max(0, (float) $safetyRequest->approved_quantity - (float) $safetyRequest->issued_quantity);

        if ($remaining > 0 && $quantity > $remaining) {
            return back()->with('error', 'Issue quantity cannot be more than remaining approved quantity.');
        }

        DB::transaction(function () use ($safetyRequest, $quantity, $sourceSiteId, $data) {
            $this->removeStock($safetyRequest->safety_item_id, $sourceSiteId, $quantity, SafetyStockMovement::ISSUE_OUT, SafetyIssue::class, null, $data['remarks'] ?? 'Safety item issued');

            $issue = SafetyIssue::query()->create([
                'safety_request_id' => $safetyRequest->id,
                'safety_item_id' => $safetyRequest->safety_item_id,
                'labour_site_id' => $safetyRequest->labour_site_id,
                'project_id' => $safetyRequest->project_id,
                'project_task_id' => $safetyRequest->project_task_id,
                'issued_quantity' => $quantity,
                'issued_by' => session('admin_user_id'),
                'issued_at' => now(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            SafetyStockMovement::query()
                ->where('reference_type', SafetyIssue::class)
                ->whereNull('reference_id')
                ->latest()
                ->limit(1)
                ->update(['reference_id' => $issue->id]);

            $safetyRequest->issued_quantity = (float) $safetyRequest->issued_quantity + $quantity;
            $safetyRequest->status = (float) $safetyRequest->issued_quantity >= (float) $safetyRequest->approved_quantity ? 'issued' : 'partially_approved';
            $safetyRequest->save();
        });

        return back()->with('success', 'Safety item issued and stock deducted.');
    }

    private function addStock(int $itemId, ?int $siteId, float $quantity, string $type, ?string $referenceType, ?int $referenceId, ?string $remarks): void
    {
        $stock = $this->stockRow($itemId, $siteId);
        $stock->available_quantity = (float) $stock->available_quantity + $quantity;
        $stock->save();
        $this->movement($itemId, $siteId, $type, $quantity, (float) $stock->available_quantity, $referenceType, $referenceId, $remarks);
    }

    private function removeStock(int $itemId, ?int $siteId, float $quantity, string $type, ?string $referenceType, ?int $referenceId, ?string $remarks): void
    {
        $stock = $this->stockRow($itemId, $siteId);
        if ((float) $stock->available_quantity < $quantity) {
            throw \Illuminate\Validation\ValidationException::withMessages(['issued_quantity' => 'Selected safety stock source does not have enough quantity.']);
        }

        $stock->available_quantity = (float) $stock->available_quantity - $quantity;
        $stock->save();
        $this->movement($itemId, $siteId, $type, $quantity, (float) $stock->available_quantity, $referenceType, $referenceId, $remarks);
    }

    private function stockRow(int $itemId, ?int $siteId): SafetyStock
    {
        return SafetyStock::query()->firstOrCreate([
            'company_id' => app(Tenant::class)->id(),
            'safety_item_id' => $itemId,
            'labour_site_id' => $siteId,
        ], [
            'available_quantity' => 0,
        ]);
    }

    private function movement(int $itemId, ?int $siteId, string $type, float $quantity, float $balanceAfter, ?string $referenceType, ?int $referenceId, ?string $remarks): void
    {
        SafetyStockMovement::query()->create([
            'safety_item_id' => $itemId,
            'labour_site_id' => $siteId,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'remarks' => $remarks,
        ]);
    }

    private function activeItems()
    {
        return SafetyItem::query()->forCurrentCompany()->where('is_active', true)->orderBy('name')->get();
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

        if (! $schema->hasTable('safety_stocks')) {
            $schema->create('safety_stocks', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id')->nullable(); $table->unsignedBigInteger('safety_item_id'); $table->unsignedBigInteger('labour_site_id')->nullable(); $table->decimal('available_quantity', 12, 2)->default(0); $table->timestamps(); $table->unique(['company_id', 'safety_item_id', 'labour_site_id'], 'safety_stock_unique');
            });
        }

        if (! $schema->hasTable('safety_purchases')) {
            $schema->create('safety_purchases', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id')->nullable(); $table->unsignedBigInteger('safety_item_id'); $table->unsignedBigInteger('stock_labour_site_id')->nullable(); $table->date('purchase_date')->nullable(); $table->string('supplier_name')->nullable(); $table->string('bill_no')->nullable(); $table->decimal('quantity', 12, 2); $table->decimal('rate', 12, 2)->default(0); $table->decimal('total_amount', 15, 2)->default(0); $table->text('remarks')->nullable(); $table->timestamps();
            });
        }

        if (! $schema->hasTable('safety_requests')) {
            $schema->create('safety_requests', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id')->nullable(); $table->unsignedBigInteger('safety_item_id'); $table->unsignedBigInteger('labour_site_id')->nullable(); $table->unsignedBigInteger('project_id')->nullable(); $table->unsignedBigInteger('project_task_id')->nullable(); $table->date('request_date')->nullable(); $table->decimal('requested_quantity', 12, 2); $table->decimal('approved_quantity', 12, 2)->default(0); $table->decimal('issued_quantity', 12, 2)->default(0); $table->string('requested_by')->nullable(); $table->string('priority', 30)->default('normal'); $table->string('status', 40)->default('pending'); $table->text('purpose')->nullable(); $table->text('admin_note')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->timestamps();
            });
        }

        if (! $schema->hasTable('safety_issues')) {
            $schema->create('safety_issues', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id')->nullable(); $table->unsignedBigInteger('safety_request_id')->nullable(); $table->unsignedBigInteger('safety_item_id'); $table->unsignedBigInteger('labour_site_id')->nullable(); $table->unsignedBigInteger('project_id')->nullable(); $table->unsignedBigInteger('project_task_id')->nullable(); $table->decimal('issued_quantity', 12, 2); $table->unsignedBigInteger('issued_by')->nullable(); $table->timestamp('issued_at')->nullable(); $table->text('remarks')->nullable(); $table->timestamps();
            });
        }

        if (! $schema->hasTable('safety_stock_movements')) {
            $schema->create('safety_stock_movements', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('company_id')->nullable(); $table->unsignedBigInteger('safety_item_id'); $table->unsignedBigInteger('labour_site_id')->nullable(); $table->unsignedBigInteger('project_id')->nullable(); $table->unsignedBigInteger('project_task_id')->nullable(); $table->string('type', 40); $table->decimal('quantity', 12, 2); $table->decimal('balance_after', 12, 2)->default(0); $table->string('reference_type')->nullable(); $table->unsignedBigInteger('reference_id')->nullable(); $table->text('remarks')->nullable(); $table->timestamps();
            });
        }
    }
}
