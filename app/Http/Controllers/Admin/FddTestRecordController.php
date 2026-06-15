<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FddRoadSection;
use App\Models\FddTestRecord;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FddTestRecordController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $records = $this->filteredRecords($filters)->get();
        $sections = FddRoadSection::query()
            ->forCurrentCompany()
            ->orderBy('group_number')
            ->orderBy('name')
            ->get();

        return view('admin.fdd-test-records.index', [
            'records' => $records,
            'groupedRecords' => $this->groupRecords($records),
            'filters' => $filters,
            'materials' => FddTestRecord::query()
                ->forCurrentCompany()
                ->whereNotNull('material')
                ->distinct()
                ->orderBy('material')
                ->pluck('material'),
            'sections' => $sections,
            'summary' => [
                'total_records' => $records->count(),
                'sections' => $records->unique(fn (FddTestRecord $record) => $record->fdd_road_section_id ?: $record->section_name)->count(),
                'materials' => $records->pluck('material')->filter()->unique()->count(),
                'latest_date' => $records->pluck('test_date')->filter()->max(),
            ],
        ]);
    }

    public function storeRoadSection(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
        ]);

        $data = $request->validate([
            'group_number' => ['required', 'integer', 'min:1', 'max:999', Rule::unique('fdd_road_sections', 'group_number')],
            'name' => ['required', 'string', 'max:150', Rule::unique('fdd_road_sections', 'name')],
        ]);

        FddRoadSection::query()->create([
            'group_number' => $data['group_number'],
            'name' => trim($data['name']),
        ]);

        return redirect()
            ->route('admin.fdd-test-records.index')
            ->with('success', 'Road / section master added successfully.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedRecord($request);
        $section = FddRoadSection::query()->forCurrentCompany()->findOrFail($data['fdd_road_section_id']);

        $data['group_number'] = $section->group_number;
        $data['section_name'] = $section->name;
        $data['sort_order'] = $data['sort_order'] ?? $this->nextSortOrder($section->id);

        FddTestRecord::query()->create($data);

        return redirect()
            ->route('admin.fdd-test-records.index')
            ->with('success', 'FDD test record added successfully.');
    }

    public function edit(FddTestRecord $fddTestRecord): View
    {
        return view('admin.fdd-test-records.edit', [
            'record' => $fddTestRecord,
            'sections' => FddRoadSection::query()
                ->forCurrentCompany()
                ->orderBy('group_number')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, FddTestRecord $fddTestRecord): RedirectResponse
    {
        $data = $this->validatedRecord($request);
        $section = FddRoadSection::query()->forCurrentCompany()->findOrFail($data['fdd_road_section_id']);

        $data['group_number'] = $section->group_number;
        $data['section_name'] = $section->name;
        $data['sort_order'] = $data['sort_order'] ?? $fddTestRecord->sort_order;

        $fddTestRecord->update($data);

        return redirect()
            ->route('admin.fdd-test-records.index')
            ->with('success', 'FDD test record updated successfully.');
    }

    public function destroy(FddTestRecord $fddTestRecord): RedirectResponse
    {
        $fddTestRecord->delete();

        return redirect()
            ->route('admin.fdd-test-records.index')
            ->with('success', 'FDD test record deleted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $records = $this->filteredRecords($filters)->get();
        $filename = 'fdd-test-records-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sr. No.', 'Road / Section', 'Date', 'Material', 'Location']);

            foreach ($records as $record) {
                fputcsv($handle, [
                    $record->roadSection?->group_number ?? $record->group_number,
                    $record->roadSection?->name ?? $record->section_name,
                    $record->test_date?->format('d-m-Y') ?? '',
                    $record->material,
                    $record->location,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'road_section_id' => ['nullable', 'exists:fdd_road_sections,id'],
            'material' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function filteredRecords(array $filters)
    {
        return FddTestRecord::query()
            ->forCurrentCompany()
            ->with('roadSection')
            ->when($filters['from_date'] ?? null, fn ($query, $date) => $query->whereDate('test_date', '>=', Carbon::parse($date)->toDateString()))
            ->when($filters['to_date'] ?? null, fn ($query, $date) => $query->whereDate('test_date', '<=', Carbon::parse($date)->toDateString()))
            ->when($filters['road_section_id'] ?? null, fn ($query, $sectionId) => $query->where('fdd_road_section_id', $sectionId))
            ->when($filters['material'] ?? null, fn ($query, $material) => $query->where('material', $material))
            ->orderBy('group_number')
            ->orderBy('sort_order')
            ->orderBy('test_date')
            ->orderBy('id');
    }

    private function groupRecords(Collection $records): Collection
    {
        return $records->groupBy(fn (FddTestRecord $record) => $record->fdd_road_section_id ?: 'legacy-' . $record->section_name);
    }

    private function validatedRecord(Request $request): array
    {
        $data = $request->validate([
            'fdd_road_section_id' => ['required', 'exists:fdd_road_sections,id'],
            'test_date' => ['nullable', 'date'],
            'material' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['material'] = trim($data['material']);
        $data['location'] = trim($data['location']);

        return $data;
    }

    private function nextSortOrder(int $sectionId): int
    {
        return (int) FddTestRecord::query()
            ->forCurrentCompany()
            ->where('fdd_road_section_id', $sectionId)
            ->max('sort_order') + 1;
    }
}
