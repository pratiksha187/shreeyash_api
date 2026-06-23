<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyProgressReport;
use App\Models\DailyProgressReportPhoto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyProgressReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $fromDate = isset($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : today()->toDateString();
        $userId = $filters['user_id'] ?? null;

        $baseQuery = DailyProgressReport::query()
            ->forCurrentCompany()
            ->whereBetween('dpr_date', [$fromDate, $toDate])
            ->when($userId, fn ($query) => $query->where('user_id', $userId));

        $allReports = (clone $baseQuery)
            ->with(['user:id,name,mobile,designation', 'hours.photos'])
            ->withCount(['hours', 'photos'])
            ->orderByDesc('dpr_date')
            ->orderByDesc('id')
            ->get();

        $reportGroups = $this->groupReportsByEngineerDate($allReports);
        $reports = $this->paginateReportGroups($reportGroups, $request);

        return view('admin.dpr-reports.index', [
            'employees' => User::query()->forCurrentCompany()->employees()->orderBy('name')->get(['id', 'name', 'mobile']),
            'reports' => $reports,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedUserId' => $userId,
            'summary' => [
                'total_reports' => $reportGroups->count(),
                'engineers' => $reportGroups->pluck('user_id')->filter()->unique()->count(),
                'hours' => $reportGroups->sum('hours_count'),
                'photos' => $reportGroups->sum('photos_count'),
            ],
        ]);
    }   

    public function show(int $dailyProgressReport): View|RedirectResponse
    {
        $selectedReport = DailyProgressReport::query()
            ->forCurrentCompany()
            ->find($dailyProgressReport);

        if (! $selectedReport) {
            return redirect()
                ->route('admin.dpr-reports.index')
                ->with('error', 'DPR report not found for this company/admin.');
        }

        $reports = DailyProgressReport::query()
            ->forCurrentCompany()
            ->with(['user:id,name,mobile,designation', 'hours.photos'])
            ->withCount(['hours', 'photos'])
            ->where('user_id', $selectedReport->user_id)
            ->whereDate('dpr_date', $selectedReport->dpr_date)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $reportGroup = $this->makeReportGroup($reports);

        return view('admin.dpr-reports.show', [
            'report' => $reportGroup,
            'reports' => $reports,
        ]);
    }

    public function photo(DailyProgressReportPhoto $photo): StreamedResponse|BinaryFileResponse
    {
        $photoPath = $this->publicPhotoPath($photo->photo_path);

        if ($photoPath) {
            return Storage::disk('public')->response($photoPath);
        }

        $publicStoragePath = $this->publicStoragePhotoPath($photo->photo_path);

        if ($publicStoragePath) {
            return response()->file($publicStoragePath);
        }

        abort(404);
    }

    private function groupReportsByEngineerDate(Collection $reports): Collection
    {
        return $reports
            ->groupBy(fn (DailyProgressReport $report) => $report->user_id . '|' . $report->dpr_date?->toDateString())
            ->map(fn (Collection $reports) => $this->makeReportGroup($reports))
            ->sortByDesc(fn (object $group) => ($group->dpr_date?->format('Y-m-d') ?? '') . ' ' . ($group->created_at?->format('H:i:s') ?? ''))
            ->values();
    }

    private function makeReportGroup(Collection $reports): object
    {
        $orderedReports = $reports
            ->sortByDesc(fn (DailyProgressReport $report) => $report->created_at?->timestamp ?? 0)
            ->values();
        $latestReport = $orderedReports->first();

        return (object) [
            'id' => $latestReport?->id,
            'user_id' => $latestReport?->user_id,
            'user' => $latestReport?->user,
            'dpr_date' => $latestReport?->dpr_date,
            'site_project' => $orderedReports->pluck('site_project')->filter()->unique()->implode(', '),
            'work_summary' => $orderedReports->pluck('work_summary')->filter()->unique()->implode(' | '),
            'reports_count' => $orderedReports->count(),
            'hours_count' => $orderedReports->sum('hours_count'),
            'photos_count' => $orderedReports->sum('photos_count'),
            'created_at' => $latestReport?->created_at,
        ];
    }

    private function paginateReportGroups(Collection $reportGroups, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));

        return new LengthAwarePaginator(
            $reportGroups->forPage($page, $perPage)->values(),
            $reportGroups->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function publicPhotoPath(?string $photoPath): ?string
    {
        return $this->photoPathCandidates($photoPath)
            ->first(fn (string $path) => Storage::disk('public')->exists($path));
    }

    private function publicStoragePhotoPath(?string $photoPath): ?string
    {
        return $this->photoPathCandidates($photoPath)
            ->map(fn (string $path) => public_path('storage/' . $path))
            ->first(fn (string $path) => is_file($path));
    }

    private function photoPathCandidates(?string $photoPath): \Illuminate\Support\Collection
    {
        if (! $photoPath) {
            return collect();
        }

        $normalizedPath = str_replace('\\', '/', ltrim($photoPath, '/\\'));

        return collect([
            $photoPath,
            $normalizedPath,
            preg_replace('#^public/#', '', $normalizedPath),
            preg_replace('#^public/storage/#', '', $normalizedPath),
            preg_replace('#^storage/#', '', $normalizedPath),
            preg_replace('#^storage/app/public/#', '', $normalizedPath),
            preg_replace('#^dpr/#', 'engg_dpr/', $normalizedPath),
            preg_replace('#^public/dpr/#', 'engg_dpr/', $normalizedPath),
            preg_replace('#^public/storage/dpr/#', 'engg_dpr/', $normalizedPath),
            preg_replace('#^storage/dpr/#', 'engg_dpr/', $normalizedPath),
            preg_replace('#^storage/app/public/dpr/#', 'engg_dpr/', $normalizedPath),
            preg_replace('#^engg_dpr/#', 'dpr/', $normalizedPath),
        ])
            ->filter()
            ->map(fn (string $path) => str_replace('\\', '/', $path))
            ->reject(fn (string $path) => str_contains($path, '..'))
            ->unique()
            ->values();
    }
}
