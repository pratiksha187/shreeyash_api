<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyProgressReport;
use App\Models\DailyProgressReportHour;
use App\Models\DailyProgressReportPhoto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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

        $reports = (clone $baseQuery)
            ->with(['user:id,name,mobile,designation', 'hours.photos'])
            ->orderByDesc('dpr_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $hoursCount = DailyProgressReportHour::query()
            ->whereHas('report', fn ($query) => $this->applyReportFilters($query, $fromDate, $toDate, $userId))
            ->count();

        $photosCount = DailyProgressReportPhoto::query()
            ->whereHas('hour.report', fn ($query) => $this->applyReportFilters($query, $fromDate, $toDate, $userId))
            ->count();

        return view('admin.dpr-reports.index', [
            'employees' => User::query()->forCurrentCompany()->employees()->orderBy('name')->get(['id', 'name', 'mobile']),
            'reports' => $reports,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedUserId' => $userId,
            'summary' => [
                'total_reports' => (clone $baseQuery)->count(),
                'engineers' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
                'hours' => $hoursCount,
                'photos' => $photosCount,
            ],
        ]);
    }   

    public function photo(DailyProgressReportPhoto $photo): StreamedResponse
    {
        $photoPath = $this->publicPhotoPath($photo->photo_path);

        if (! $photoPath) {
            abort(404);
        }

        return Storage::disk('public')->response($photoPath);
    }

    private function applyReportFilters($query, string $fromDate, string $toDate, ?string $userId): void
    {
        $query
            ->whereBetween('dpr_date', [$fromDate, $toDate])
            ->forCurrentCompany()
            ->when($userId, fn ($query) => $query->where('user_id', $userId));
    }

    private function publicPhotoPath(?string $photoPath): ?string
    {
        if (! $photoPath) {
            return null;
        }

        $paths = collect([
            $photoPath,
            ltrim($photoPath, '/\\'),
            preg_replace('#^public[/\\\\]#', '', ltrim($photoPath, '/\\')),
            preg_replace('#^storage[/\\\\]#', '', ltrim($photoPath, '/\\')),
        ])
            ->filter()
            ->map(fn (string $path) => str_replace('\\', '/', $path))
            ->unique();

        return $paths->first(fn (string $path) => Storage::disk('public')->exists($path));
    }
}
