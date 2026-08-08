<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Labour;
use App\Models\LabourCostingRecord;
use App\Models\LabourSite;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LabourCostingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'labour_site_id' => ['nullable', 'integer'],
            'contractor_id' => ['nullable', 'integer'],
            'work_category' => ['nullable', 'string', 'max:120'],
        ]);

        $fromDate = isset($filters['from_date']) ? Carbon::parse($filters['from_date'])->toDateString() : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date']) ? Carbon::parse($filters['to_date'])->toDateString() : today()->toDateString();

        $query = LabourCostingRecord::query()
            ->forCurrentCompany()
            ->whereBetween('work_date', [$fromDate, $toDate])
            ->when($filters['labour_site_id'] ?? null, fn ($query, $siteId) => $query->where('labour_site_id', $siteId))
            ->when($filters['contractor_id'] ?? null, fn ($query, $contractorId) => $query->where('contractor_id', $contractorId))
            ->when($filters['work_category'] ?? null, fn ($query, $category) => $query->where('work_category', 'like', "%{$category}%"));

        return view('admin.labour-attendance.costing', [
            'records' => (clone $query)->with(['site', 'contractor', 'labour'])->latest('work_date')->latest('id')->paginate(20)->withQueryString(),
            'sites' => LabourSite::query()->forCurrentCompany()->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::query()->forCurrentCompany()->orderBy('name')->get(['id', 'name']),
            'labours' => Labour::query()->forCurrentCompany()->with('contractor:id,name')->orderBy('name')->get(),
            'filters' => $filters,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'summary' => [
                'records' => (clone $query)->count(),
                'payable_days' => (float) (clone $query)->sum('payable_days'),
                'overtime_hours' => (float) (clone $query)->sum('overtime_hours'),
                'total_amount' => (float) (clone $query)->sum('total_amount'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LabourCostingRecord::query()->create($this->withCalculatedAmounts($this->validatedData($request)));

        return back()->with('success', 'Labour costing record added successfully.');
    }

    public function update(Request $request, int $record): RedirectResponse
    {
        $record = LabourCostingRecord::query()->forCurrentCompany()->findOrFail($record);
        $record->update($this->withCalculatedAmounts($this->validatedData($request)));

        return back()->with('success', 'Labour costing record updated successfully.');
    }

    public function destroy(int $record): RedirectResponse
    {
        $record = LabourCostingRecord::query()->forCurrentCompany()->findOrFail($record);
        $record->delete();

        return back()->with('success', 'Labour costing record deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'labour_id' => ['required', 'integer', Rule::exists($this->tenantTable('labours'), 'id')->where('company_id', app(Tenant::class)->id())],
            'contractor_id' => ['nullable', 'integer', Rule::exists($this->tenantTable('contractors'), 'id')->where('company_id', app(Tenant::class)->id())],
            'labour_site_id' => ['nullable', 'integer', Rule::exists($this->tenantTable('labour_sites'), 'id')->where('company_id', app(Tenant::class)->id())],
            'work_date' => ['required', 'date'],
            'labour_type' => ['required', Rule::in(['permanent', 'daily_wage'])],
            'shift' => ['required', Rule::in(['day', 'night', 'general'])],
            'work_category' => ['nullable', 'string', 'max:120'],
            'payable_days' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'work_hours' => ['nullable', 'numeric', 'min:0', 'max:744'],
            'overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:744'],
            'daily_wage_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['required', Rule::in(['draft', 'approved', 'paid'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function withCalculatedAmounts(array $data): array
    {
        $labour = Labour::query()->forCurrentCompany()->findOrFail($data['labour_id']);

        $data['contractor_id'] = $data['contractor_id'] ?? $labour->contractor_id;
        $data['labour_type'] = $data['labour_type'] ?? ($labour->labour_type ?: 'daily_wage');
        $data['work_category'] = $data['work_category'] ?? $labour->work_category;
        $data['daily_wage_rate'] = (float) ($data['daily_wage_rate'] ?? $labour->daily_wage_rate ?? 0);
        $data['overtime_rate'] = (float) ($data['overtime_rate'] ?? $labour->overtime_rate ?? 0);
        $data['payable_days'] = (float) ($data['payable_days'] ?? 1);
        $data['work_hours'] = (float) ($data['work_hours'] ?? 0);
        $data['overtime_hours'] = (float) ($data['overtime_hours'] ?? 0);
        $data['base_amount'] = $data['payable_days'] * $data['daily_wage_rate'];
        $data['overtime_amount'] = $data['overtime_hours'] * $data['overtime_rate'];
        $data['total_amount'] = $data['base_amount'] + $data['overtime_amount'];

        return $data;
    }

    private function tenantTable(string $table): string
    {
        $connectionName = app(Tenant::class)->connectionName();

        return $connectionName ? $connectionName.'.'.$table : $table;
    }
}
