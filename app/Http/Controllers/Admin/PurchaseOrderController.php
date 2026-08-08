<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(): View
    {
        $orders = PurchaseOrder::query()
            ->forCurrentCompany()
            ->withCount('items')
            ->latest('po_date')
            ->latest('id')
            ->paginate(20);

        return view('admin.purchase-orders.index', [
            'orders' => $orders,
            'nextPoNo' => $this->nextPoNo(),
            'suppliers' => Supplier::query()->forCurrentCompany()->where('is_active', true)->orderBy('name')->get(),
            'units' => Unit::query()->forCurrentCompany()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $order = DB::transaction(function () use ($data) {
            $items = collect($data['items'])
                ->filter(fn (array $item) => filled($item['item_description'] ?? null))
                ->values();

            if ($items->isEmpty()) {
                abort(422, 'Add at least one PO item.');
            }

            $subtotal = $items->sum(function (array $item) {
                return ((float) ($item['quantity'] ?? 0)) * ((float) ($item['rate'] ?? 0));
            });

            $cgst = (float) ($data['cgst_amount'] ?? 0);
            $sgst = (float) ($data['sgst_amount'] ?? 0);
            $igst = (float) ($data['igst_amount'] ?? 0);
            $total = $subtotal + $cgst + $sgst + $igst;
            $tdsPercent = (float) ($data['tds_percent'] ?? 0);
            $tdsAmount = round($total * ($tdsPercent / 100), 2);

            $order = PurchaseOrder::query()->create([
                ...collect($data)->except('items')->all(),
                'po_no' => $data['po_no'] ?: $this->nextPoNo(),
                'subtotal' => $subtotal,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => $igst,
                'total_amount' => $total,
                'tds_percent' => $tdsPercent,
                'tds_amount' => $tdsAmount,
                'net_payable_amount' => $total - $tdsAmount,
            ]);

            $items->each(function (array $item, int $index) use ($order) {
                $quantity = (float) ($item['quantity'] ?? 0);
                $rate = (float) ($item['rate'] ?? 0);

                $order->items()->create([
                    'sort_order' => $index + 1,
                    'item_description' => $item['item_description'],
                    'hsn_code' => $item['hsn_code'] ?? null,
                    'quantity' => $quantity,
                    'unit' => $item['unit'] ?? null,
                    'rate' => $rate,
                    'amount' => $quantity * $rate,
                ]);
            });

            return $order;
        });

        return redirect()
            ->route('admin.purchase-orders.index')
            ->with('success', 'Purchase order '.$order->po_no.' created successfully.');
    }

    public function download(int $purchaseOrder): Response
    {
        $order = PurchaseOrder::query()
            ->forCurrentCompany()
            ->with('items')
            ->findOrFail($purchaseOrder);

        $pdf = Pdf::loadView('admin.purchase-orders.pdf', [
            'order' => $order,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('purchase-order-'.str_replace(['/', '\\'], '-', $order->po_no).'.pdf');
    }

    public function destroy(int $purchaseOrder): RedirectResponse
    {
        $order = PurchaseOrder::query()->forCurrentCompany()->findOrFail($purchaseOrder);
        $order->delete();

        return back()->with('success', 'Purchase order deleted successfully.');
    }

    private function nextPoNo(): string
    {
        $date = now();
        $fyStartYear = $date->month >= 4 ? $date->year : $date->year - 1;
        $fy = $fyStartYear.'-'.substr((string) ($fyStartYear + 1), -2);
        $prefix = 'SC/';

        $last = PurchaseOrder::query()
            ->forCurrentCompany()
            ->where('po_no', 'like', $prefix.'%/'.$fy)
            ->latest('id')
            ->value('po_no');

        $next = 1;
        if ($last && preg_match('/^SC\/(\d+)\/'.preg_quote($fy, '/').'$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT).'/'.$fy;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'po_no' => ['nullable', 'string', 'max:80'],
            'po_date' => ['required', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_address' => ['nullable', 'string', 'max:2000'],
            'supplier_gstin' => ['nullable', 'string', 'max:40'],
            'supplier_ref' => ['nullable', 'string', 'max:120'],
            'supplier_tds_section' => ['nullable', 'string', 'max:80'],
            'tds_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'e_invoice_applicable' => ['nullable', 'boolean'],
            'e_way_bill_applicable' => ['nullable', 'boolean'],
            'vendor_reconciliation_status' => ['nullable', 'string', 'max:80'],
            'auditor_export_note' => ['nullable', 'string', 'max:3000'],
            'dispatched_through' => ['nullable', 'string', 'max:120'],
            'destination' => ['nullable', 'string', 'max:160'],
            'delivery_location' => ['nullable', 'string', 'max:2000'],
            'cgst_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'sgst_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'igst_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'terms' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(['draft', 'issued', 'closed', 'cancelled'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_description' => ['nullable', 'string', 'max:1000'],
            'items.*.hsn_code' => ['nullable', 'string', 'max:80'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999.999'],
            'items.*.unit' => ['nullable', 'string', 'max:40'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);
    }
}
