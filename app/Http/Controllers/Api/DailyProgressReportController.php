<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyProgressReport;
use App\Models\DailyProgressReportPhoto;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyProgressReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = DailyProgressReport::query()
            ->forCurrentCompany()
            ->with(['hours.photos'])
            ->where('user_id', $request->user()->id);

        if (isset($filters['from_date'])) {
            $query->whereDate('dpr_date', '>=', Carbon::parse($filters['from_date'])->toDateString());
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('dpr_date', '<=', Carbon::parse($filters['to_date'])->toDateString());
        }

        $reports = $query
            ->orderByDesc('dpr_date')
            ->orderByDesc('id')
            ->limit($filters['limit'] ?? 30)
            ->get()
            ->map(fn (DailyProgressReport $report) => $this->reportPayload($report));

        return response()->json([
            'message' => 'DPR reports fetched successfully.',
            'dprs' => $reports,
        ]);
    }

    public function show(Request $request, int $dailyProgressReport): JsonResponse
    {
        $dailyProgressReport = DailyProgressReport::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->findOrFail($dailyProgressReport);

        $dailyProgressReport->load(['hours.photos']);

        return response()->json([
            'message' => 'DPR report fetched successfully.',
            'dpr' => $this->reportPayload($dailyProgressReport),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeInput($request);

        $data = $request->validate([
            'dpr_date' => ['required', 'date'],
            'site_project' => ['required', 'string', 'max:255'],
            'work_summary' => ['required', 'string', 'max:5000'],
            'hours' => ['required', 'array', 'min:1', 'max:24'],
            'hours.*.hour_number' => ['nullable', 'integer', 'min:1', 'max:24'],
            'hours.*.time' => ['required', 'string', 'max:20'],
            'hours.*.remark' => ['nullable', 'string', 'max:2000'],
            'hours.*.photos' => ['nullable', 'array'],
            'hours.*.photos.*' => ['image', 'max:5120'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        $hours = collect($data['hours'])
            ->values()
            ->map(function (array $hour, int $index) {
                return [
                    'hour_number' => (int) ($hour['hour_number'] ?? $index + 1),
                    'work_time' => $this->parseTime($hour['time'], 'hours.' . $index . '.time'),
                    'remark' => $hour['remark'] ?? null,
                ];
            });

        if ($hours->pluck('hour_number')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'hours' => 'Hour numbers must be unique for one DPR.',
            ]);
        }

        $user = $request->user();
        $storedPaths = [];
        $report = null;

        try {
            DB::transaction(function () use ($request, $data, $hours, $user, &$storedPaths, &$report) {
                $report = new DailyProgressReport([
                    'user_id' => $user->id,
                    'dpr_date' => Carbon::parse($data['dpr_date'])->toDateString(),
                ]);

                $report->fill([
                    'site_project' => $data['site_project'],
                    'work_summary' => $data['work_summary'],
                ]);
                $report->save();

                foreach ($hours as $index => $hour) {
                    $hourModel = $report->hours()->create($hour);

                    Storage::disk('public')->makeDirectory('engg_dpr');

                    foreach ($this->filesForHour($request, $index) as $photo) {
                        $path = $photo->store(
                            'engg_dpr/' . $user->id . '/' . $report->id . '/hour-' . $hour['hour_number'],
                            'public'
                        );
                        $this->mirrorPhotoToPublicStorage($path);
                        $storedPaths[] = $path;

                        $hourModel->photos()->create([
                            'photo_path' => $path,
                            'original_name' => $photo->getClientOriginalName(),
                            'mime_type' => $photo->getClientMimeType(),
                            'file_size' => $photo->getSize(),
                        ]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        $report->load(['hours.photos']);

        return response()->json([
            'message' => 'DPR submitted successfully.',
            'dpr' => $this->reportPayload($report),
        ], 201);
    }

    public function photo(Request $request, int $photo): StreamedResponse|BinaryFileResponse
    {
        $photo = DailyProgressReportPhoto::query()
            ->whereHas('hour.report', function ($query) use ($request) {
                $query
                    ->forCurrentCompany()
                    ->where('user_id', $request->user()->id);
            })
            ->findOrFail($photo);

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

    private function normalizeInput(Request $request): void
    {
        $data = [];

        if (! $request->has('dpr_date')) {
            foreach (['date', 'dprDate', 'dpr_date'] as $key) {
                if ($request->has($key)) {
                    $data['dpr_date'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('site_project')) {
            foreach (['site', 'project', 'project_name', 'site_name', 'siteProject'] as $key) {
                if ($request->has($key)) {
                    $data['site_project'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('work_summary')) {
            foreach (['summary', 'workSummary', 'work_summary'] as $key) {
                if ($request->has($key)) {
                    $data['work_summary'] = $request->input($key);
                    break;
                }
            }
        }

        $hours = $request->input('hours');

        if (is_string($hours)) {
            $decodedHours = json_decode($hours, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $hours = $decodedHours;
            }
        }

        if (is_array($hours)) {
            $data['hours'] = collect($hours)
                ->map(function ($hour) {
                    if (! is_array($hour)) {
                        return $hour;
                    }

                    if (! isset($hour['time'])) {
                        foreach (['work_time', 'hour_time'] as $key) {
                            if (isset($hour[$key])) {
                                $hour['time'] = $hour[$key];
                                break;
                            }
                        }
                    }

                    if (! isset($hour['remark']) && isset($hour['remarks'])) {
                        $hour['remark'] = $hour['remarks'];
                    }

                    if (! isset($hour['hour_number'])) {
                        foreach (['hour', 'number'] as $key) {
                            if (isset($hour[$key])) {
                                $hour['hour_number'] = $hour[$key];
                                break;
                            }
                        }
                    }

                    return $hour;
                })
                ->all();
        }

        if ($data) {
            $request->merge($data);
        }
    }

    private function parseTime(string $time, string $field): string
    {
        $time = trim($time);
        $formats = ['H:i:s', 'H:i', 'h:i A', 'h:iA', 'g:i A', 'g:iA'];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, strtoupper($time));

                if ($parsed !== false && $parsed->format($format) === strtoupper($time)) {
                    return $parsed->format('H:i:s');
                }
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'The hour time must be a valid time.',
            ]);
        }
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function filesForHour(Request $request, int $index): array
    {
        $files = $request->file('hours.' . $index . '.photos', []);
        $files = $files instanceof UploadedFile ? [$files] : (array) $files;

        if ($index === 0) {
            $topLevelFiles = $request->file('photos', []);
            $topLevelFiles = $topLevelFiles instanceof UploadedFile ? [$topLevelFiles] : (array) $topLevelFiles;
            $files = array_merge($files, $topLevelFiles);
        }

        return array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));
    }

    private function reportPayload(DailyProgressReport $report): array
    {
        $report->loadMissing(['user:id,name,mobile,designation', 'hours.photos']);

        return [
            'id' => $report->id,
            'dpr_date' => $report->dpr_date?->toDateString(),
            'date_display' => $report->dpr_date?->format('d M Y'),
            'site_project' => $report->site_project,
            'work_summary' => $report->work_summary,
            'engineer' => [
                'id' => $report->user?->id,
                'name' => $report->user?->name,
                'mobile' => $report->user?->mobile,
                'designation' => $report->user?->designation,
            ],
            'hour_count' => $report->hours->count(),
            'photo_count' => $report->hours->sum(fn ($hour) => $hour->photos->count()),
            'hours' => $report->hours
                ->sortBy('hour_number')
                ->values()
                ->map(fn ($hour) => [
                    'id' => $hour->id,
                    'hour_number' => $hour->hour_number,
                    'time' => $hour->work_time,
                    'time_display' => Carbon::parse($hour->work_time)->format('h:i A'),
                    'remark' => $hour->remark,
                    'photos' => $hour->photos->map(fn (DailyProgressReportPhoto $photo) => [
                        'id' => $photo->id,
                        'url' => $photo->publicUrl(),
                        'photo_url' => $photo->publicUrl(),
                        'api_url' => route('api.dpr-photos.show', $photo),
                        'path' => $photo->photo_path,
                        'original_name' => $photo->original_name,
                        'mime_type' => $photo->mime_type,
                        'file_size' => $photo->file_size,
                    ])->values(),
                ]),
            'submitted_at' => $report->created_at,
            'updated_at' => $report->updated_at,
        ];
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

    private function mirrorPhotoToPublicStorage(string $path): void
    {
        $sourcePath = Storage::disk('public')->path($path);
        $targetPath = public_path('storage/' . str_replace('\\', '/', $path));

        if (! is_file($sourcePath)) {
            return;
        }

        File::ensureDirectoryExists(dirname($targetPath));
        File::copy($sourcePath, $targetPath);
    }
}
