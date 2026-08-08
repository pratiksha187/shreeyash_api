<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseWorkflowController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;

        $workflows = PurchaseWorkflow::query()
            ->forCurrentCompany()
            ->when($status, function ($query, string $status) {
                $query->where(function ($query) use ($status) {
                    $query
                        ->where('approval_status', $status)
                        ->orWhere('po_status', $status)
                        ->orWhere('grn_status', $status);
                });
            })
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('requisition_no', 'like', "%{$search}%")
                        ->orWhere('indent_no', 'like', "%{$search}%")
                        ->orWhere('material_name', 'like', "%{$search}%")
                        ->orWhere('vendor_names', 'like', "%{$search}%")
                        ->orWhere('selected_vendor', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%")
                        ->orWhere('grn_no', 'like', "%{$search}%");
                });
            })
            ->latest('requisition_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $allRows = PurchaseWorkflow::query()->forCurrentCompany();

        return view('admin.purchase-workflows.index', [
            'workflows' => $workflows,
            'status' => $status,
            'search' => $search,
            'summary' => [
                'total' => (clone $allRows)->count(),
                'pending_approval' => (clone $allRows)->where('approval_status', 'pending')->count(),
                'approved' => (clone $allRows)->where('approval_status', 'approved')->count(),
                'grn_posted' => (clone $allRows)->where('grn_status', 'posted')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PurchaseWorkflow::query()->create($this->validatedData($request));

        return back()->with('success', 'Purchase workflow added successfully.');
    }

    public function update(Request $request, int $purchaseWorkflow): RedirectResponse
    {
        $workflow = PurchaseWorkflow::query()->forCurrentCompany()->findOrFail($purchaseWorkflow);
        $workflow->update($this->validatedData($request));

        return back()->with('success', 'Purchase workflow updated successfully.');
    }

    public function destroy(int $purchaseWorkflow): RedirectResponse
    {
        $workflow = PurchaseWorkflow::query()->forCurrentCompany()->findOrFail($purchaseWorkflow);
        $workflow->delete();

        return back()->with('success', 'Purchase workflow deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'requisition_no' => ['nullable', 'string', 'max:80'],
            'requisition_date' => ['nullable', 'date'],
            'indent_no' => ['nullable', 'string', 'max:80'],
            'material_name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:40'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'vendor_enquiry_no' => ['nullable', 'string', 'max:80'],
            'vendor_names' => ['nullable', 'string', 'max:2000'],
            'quotation_summary' => ['nullable', 'string', 'max:3000'],
            'selected_vendor' => ['nullable', 'string', 'max:255'],
            'quoted_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'approval_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'approval_status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'revision'])],
            'po_no' => ['nullable', 'string', 'max:80'],
            'po_date' => ['nullable', 'date'],
            'po_status' => ['required', Rule::in(['draft', 'issued', 'closed', 'cancelled'])],
            'grn_no' => ['nullable', 'string', 'max:80'],
            'grn_date' => ['nullable', 'date'],
            'grn_status' => ['required', Rule::in(['pending', 'partial', 'posted'])],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
