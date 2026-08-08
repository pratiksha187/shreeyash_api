<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        return view('admin.units.index', [
            'units' => Unit::query()->forCurrentCompany()->orderBy('name')->paginate(30),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Unit::query()->create($this->validatedData($request));

        return back()->with('success', 'Unit added successfully.');
    }

    public function update(Request $request, int $unit): RedirectResponse
    {
        $unit = Unit::query()->forCurrentCompany()->findOrFail($unit);
        $unit->update($this->validatedData($request, $unit->id));

        return back()->with('success', 'Unit updated successfully.');
    }

    public function destroy(int $unit): RedirectResponse
    {
        $unit = Unit::query()->forCurrentCompany()->findOrFail($unit);
        $unit->delete();

        return back()->with('success', 'Unit deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique($this->tenantTable('units'), 'name')
                    ->where('company_id', app(Tenant::class)->id())
                    ->ignore($ignoreId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function tenantTable(string $table): string
    {
        $connectionName = app(Tenant::class)->connectionName();

        return $connectionName ? $connectionName.'.'.$table : $table;
    }
}
