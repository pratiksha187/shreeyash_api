<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MirFileReport;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MirFileReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $reports = $this->filteredReports($filters)->get();

        return view('admin.mir-file-reports.index', [
            'reports' => $reports,
            'filters' => $filters,
            'materials' => $this->distinctValues('material'),
            'units' => $this->distinctValues('unit'),
            'locations' => $this->distinctValues('location'),
            'summary' => [
                'total_records' => $reports->count(),
                'total_quantity' => $reports->sum(fn (MirFileReport $report) => (float) $report->quantity),
                'materials' => $reports->pluck('material')->filter()->unique()->count(),
                'latest_date' => $reports->pluck('report_date')->filter()->max(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedReport($request);
        $data['sort_order'] = $data['sort_order'] ?? $this->nextSortOrder();

        MirFileReport::query()->create($data);

        return redirect()
            ->route('admin.mir-file-reports.index')
            ->with('success', 'MIR file report added successfully.');
    }

    public function edit(MirFileReport $mirFileReport): View
    {
        return view('admin.mir-file-reports.edit', [
            'report' => $mirFileReport,
            'materials' => $this->distinctValues('material'),
            'units' => $this->distinctValues('unit'),
            'locations' => $this->distinctValues('location'),
        ]);
    }

    public function update(Request $request, MirFileReport $mirFileReport): RedirectResponse
    {
        $data = $this->validatedReport($request);
        $data['sort_order'] = $data['sort_order'] ?? $mirFileReport->sort_order;

        $mirFileReport->update($data);

        return redirect()
            ->route('admin.mir-file-reports.index')
            ->with('success', 'MIR file report updated successfully.');
    }

    public function destroy(MirFileReport $mirFileReport): RedirectResponse
    {
        $mirFileReport->delete();

        return redirect()
            ->route('admin.mir-file-reports.index')
            ->with('success', 'MIR file report deleted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $reports = $this->filteredReports($filters)->get();
        $filename = 'mir-file-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Sr. No.', 'Date', 'Material', 'QTY', 'Unit', 'Location']);

            foreach ($reports as $index => $report) {
                fputcsv($handle, [
                    $index + 1,
                    $report->report_date?->format('d-m-Y') ?? '',
                    $report->material,
                    $this->formatQuantity($report->quantity),
                    $report->unit,
                    $report->location,
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
            'material' => ['nullable', 'string', 'max:200'],
            'unit' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:200'],
        ]);
    }

    private function filteredReports(array $filters)
    {
        return MirFileReport::query()
            ->when($filters['from_date'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '>=', Carbon::parse($date)->toDateString()))
            ->when($filters['to_date'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '<=', Carbon::parse($date)->toDateString()))
            ->when($filters['material'] ?? null, fn ($query, $material) => $query->where('material', $material))
            ->when($filters['unit'] ?? null, fn ($query, $unit) => $query->where('unit', $unit))
            ->when($filters['location'] ?? null, fn ($query, $location) => $query->where('location', $location))
            ->orderByDesc('report_date')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    private function validatedReport(Request $request): array
    {
        $data = $request->validate([
            'report_date' => ['nullable', 'date'],
            'material' => ['required', 'string', 'max:200'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'unit' => ['required', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ]);

        $data['material'] = trim($data['material']);
        $data['unit'] = trim($data['unit']);
        $data['location'] = trim($data['location']);

        return $data;
    }

    private function distinctValues(string $column)
    {
        return MirFileReport::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column);
    }

    private function nextSortOrder(): int
    {
        return (int) MirFileReport::query()->max('sort_order') + 1;
    }

    private function formatQuantity(string|float|int|null $quantity): string
    {
        if ($quantity === null) {
            return '';
        }

        return rtrim(rtrim(number_format((float) $quantity, 2, '.', ''), '0'), '.');
    }
}
