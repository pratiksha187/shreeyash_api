<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        return view('admin.suppliers.index', [
            'suppliers' => Supplier::query()->forCurrentCompany()->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::query()->create($this->validatedData($request));

        return back()->with('success', 'Supplier added successfully.');
    }

    public function update(Request $request, int $supplier): RedirectResponse
    {
        $supplier = Supplier::query()->forCurrentCompany()->findOrFail($supplier);
        $supplier->update($this->validatedData($request, $supplier->id));

        return back()->with('success', 'Supplier updated successfully.');
    }

    public function destroy(int $supplier): RedirectResponse
    {
        $supplier = Supplier::query()->forCurrentCompany()->findOrFail($supplier);
        $supplier->delete();

        return back()->with('success', 'Supplier deleted successfully.');
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
}
