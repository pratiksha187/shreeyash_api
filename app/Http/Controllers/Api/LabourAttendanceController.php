<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\LabourSite;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LabourAttendanceController extends Controller
{
    private const PHOTO_MAX_UPLOAD_KB = 20480;
    private const PHOTO_UPLOAD_DIR = 'leber_image';

    public function sites(): JsonResponse
    {
        $sites = LabourSite::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (LabourSite $site) => $this->sitePayload($site));

        return response()->json([
            'message' => 'Labour sites fetched successfully.',
            'sites' => $sites,
        ]);
    }

    public function contractors(int $labourSite): JsonResponse
    {
        $labourSite = LabourSite::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->findOrFail($labourSite);

        $contractors = Contractor::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->where('labour_site_id', $labourSite->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Contractor $contractor) => $this->contractorPayload($contractor));

        return response()->json([
            'message' => 'Contractors fetched successfully.',
            'site' => $this->sitePayload($labourSite),
            'contractors' => $contractors,
        ]);
    }

    public function labours(int $contractor): JsonResponse
    {
        $contractor = Contractor::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->findOrFail($contractor);

        $labours = Labour::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->where('contractor_id', $contractor->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Labour $labour) => $this->labourPayload($labour));

        return response()->json([
            'message' => 'Labours fetched successfully.',
            'contractor' => $this->contractorPayload($contractor),
            'labours' => $labours,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'labour_id' => ['nullable', Rule::exists($this->tenantTable('labours'), 'id')],
            'approval_status' => ['nullable', Rule::in(LabourAttendance::APPROVAL_STATUSES)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = LabourAttendance::query()
            ->forCurrentCompany()
            ->with(['site', 'contractor', 'labour', 'engineer:id,name,mobile,designation'])
            ->where('engineer_user_id', $request->user()->id)
            ->when(isset($filters['from_date']), fn ($query) => $query->whereDate('attendance_date', '>=', Carbon::parse($filters['from_date'])->toDateString()))
            ->when(isset($filters['to_date']), fn ($query) => $query->whereDate('attendance_date', '<=', Carbon::parse($filters['to_date'])->toDateString()))
            ->when(isset($filters['labour_id']), fn ($query) => $query->where('labour_id', $filters['labour_id']))
            ->when(isset($filters['approval_status']), fn ($query) => $query->where('approval_status', $filters['approval_status']));

        $attendances = $query
            ->orderByDesc('attendance_date')
            ->latest()
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn (LabourAttendance $attendance) => $this->attendancePayload($attendance));

        return response()->json([
            'message' => 'Labour attendances fetched successfully.',
            'labour_attendances' => $attendances,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeInput($request);

        $data = $request->validate([
            'labour_site_id' => ['required', Rule::exists($this->tenantTable('labour_sites'), 'id')],
            'contractor_id' => ['required', Rule::exists($this->tenantTable('contractors'), 'id')],
            'labour_id' => ['required_without:labour_ids', Rule::exists($this->tenantTable('labours'), 'id')],
            'labour_ids' => ['required_without:labour_id', 'array', 'min:1', 'max:100'],
            'labour_ids.*' => ['required', Rule::exists($this->tenantTable('labours'), 'id')],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(LabourAttendance::ATTENDANCE_STATUSES)],
            'in_time' => ['nullable', 'date_format:H:i'],
            'out_time' => ['nullable', 'date_format:H:i'],
            'work_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'photo' => $request->hasFile('photo') ? ['nullable', 'image', 'max:' . self::PHOTO_MAX_UPLOAD_KB] : ['nullable', 'string'],
            'photo_base64' => ['nullable', 'string'],
        ]);

        $contractor = Contractor::query()
            ->forCurrentCompany()
            ->where('id', $data['contractor_id'])
            ->first();

        if (! $contractor) {
            throw ValidationException::withMessages([
                'contractor_id' => 'The selected contractor is invalid.',
            ]);
        }

        $labourIds = $this->labourIdsFromData($data);
        $labours = Labour::query()
            ->forCurrentCompany()
            ->where('contractor_id', $contractor->id)
            ->whereIn('id', $labourIds)
            ->get()
            ->keyBy('id');

        if ($labours->count() !== count($labourIds)) {
            throw ValidationException::withMessages([
                'labour_ids' => 'One or more selected labours are invalid for this contractor.',
            ]);
        }

        $attendanceDate = Carbon::parse($data['attendance_date'])->toDateString();
        $approvedAttendances = LabourAttendance::query()
            ->forCurrentCompany()
            ->whereIn('labour_id', $labourIds)
            ->whereDate('attendance_date', $attendanceDate)
            ->where('approval_status', 'approved')
            ->with(['site', 'contractor', 'labour', 'engineer'])
            ->get();

        if ($approvedAttendances->isNotEmpty()) {
            return response()->json([
                'message' => 'Labour attendance is already approved for this date.',
                'labour_attendances' => $approvedAttendances
                    ->map(fn (LabourAttendance $attendance) => $this->attendancePayload($attendance))
                    ->values(),
            ], 409);
        }

        $workHours = $this->workHoursFromTimes($attendanceDate, $data['in_time'] ?? null, $data['out_time'] ?? null);
        $workHours ??= $data['work_hours'] ?? null;
        $attendances = collect();
        $created = false;

        foreach ($labourIds as $labourId) {
            $attendance = LabourAttendance::query()->updateOrCreate(
                [
                    'company_id' => $request->user()->company_id,
                    'labour_id' => $labourId,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'engineer_user_id' => $request->user()->id,
                    'labour_site_id' => $data['labour_site_id'],
                    'contractor_id' => $contractor->id,
                    'status' => $data['status'],
                    'in_time' => $data['in_time'] ?? null,
                    'out_time' => $data['out_time'] ?? null,
                    'work_hours' => $workHours,
                    'remarks' => $data['remarks'] ?? null,
                    'approval_status' => 'pending',
                    'admin_note' => null,
                    'reviewed_at' => null,
                ]
            );

            $created = $created || $attendance->wasRecentlyCreated;

            if ($photoPath = $this->storeAttendancePhoto($request, $attendance)) {
                $oldPhotoPath = $attendance->photo_path;

                $attendance->update(['photo_path' => $photoPath]);

                if ($oldPhotoPath && $oldPhotoPath !== $photoPath) {
                    Storage::disk('public')->delete($oldPhotoPath);
                }
            }

            $attendance->setRelation('labour', $labours->get($labourId));
            $attendance->load(['site', 'contractor', 'engineer:id,name,mobile,designation']);
            $attendances->push($attendance);
        }

        $payloads = $attendances
            ->map(fn (LabourAttendance $attendance) => $this->attendancePayload($attendance))
            ->values();

        $response = [
            'message' => $created
                ? 'Labour attendance submitted successfully.'
                : 'Labour attendance updated and sent for approval.',
            'labour_attendances' => $payloads,
        ];

        if ($payloads->count() === 1) {
            $response['labour_attendance'] = $payloads->first();
        }

        return response()->json($response, $created ? 201 : 200);
    }

    public function photo(Request $request, int $labourAttendance): StreamedResponse|BinaryFileResponse
    {
        $labourAttendance = $this->findEmployeeLabourAttendance($request, $labourAttendance);

        $photoPath = $this->publicPhotoPath($labourAttendance->photo_path);

        if ($photoPath) {
            return Storage::disk('public')->response($photoPath);
        }

        $publicStoragePath = $this->publicStoragePhotoPath($labourAttendance->photo_path);

        if ($publicStoragePath) {
            return response()->file($publicStoragePath);
        }

        $attendancePhotoPath = $this->attendancePhotoPath($labourAttendance);

        if ($attendancePhotoPath) {
            return response()->file($attendancePhotoPath);
        }

        abort(404);
    }

    public function show(Request $request, int $labourAttendance): JsonResponse
    {
        $labourAttendance = $this->findEmployeeLabourAttendance($request, $labourAttendance);

        $labourAttendance->load(['site', 'contractor', 'labour', 'engineer:id,name,mobile,designation']);

        return response()->json([
            'message' => 'Labour attendance fetched successfully.',
            'labour_attendance' => $this->attendancePayload($labourAttendance),
        ]);
    }

    private function normalizeInput(Request $request): void
    {
        $data = [];

        if (! $request->has('labour_site_id')) {
            foreach (['site_id', 'site'] as $key) {
                if ($request->has($key)) {
                    $data['labour_site_id'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('labour_id') && $request->has('labor_id')) {
            $data['labour_id'] = $request->input('labor_id');
        }

        if (! $request->has('labour_ids')) {
            foreach (['labor_ids', 'labours', 'labors'] as $key) {
                if ($request->has($key)) {
                    $data['labour_ids'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('attendance_date')) {
            foreach (['date', 'attendanceDate'] as $key) {
                if ($request->has($key)) {
                    $data['attendance_date'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('remarks')) {
            foreach (['remark', 'note', 'notes'] as $key) {
                if ($request->has($key)) {
                    $data['remarks'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('in_time')) {
            foreach (['inTime', 'start_time', 'startTime'] as $key) {
                if ($request->has($key)) {
                    $data['in_time'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('out_time')) {
            foreach (['outTime', 'end_time', 'endTime'] as $key) {
                if ($request->has($key)) {
                    $data['out_time'] = $request->input($key);
                    break;
                }
            }
        }

        $inTime = $data['in_time'] ?? $request->input('in_time');
        if ($inTime !== null) {
            $data['in_time'] = $this->normalizeTimeValue($inTime);
        }

        $outTime = $data['out_time'] ?? $request->input('out_time');
        if ($outTime !== null) {
            $data['out_time'] = $this->normalizeTimeValue($outTime);
        }

        if (! $request->hasFile('photo')) {
            foreach (['image', 'labour_photo', 'labor_photo', 'labour_image', 'labor_image', 'attendance_photo'] as $key) {
                if ($request->hasFile($key)) {
                    $request->files->set('photo', $request->file($key));
                    break;
                }
            }
        }

        if (! $request->hasFile('photo') && ! $request->filled('photo_base64')) {
            foreach (['photo', 'image', 'labour_photo', 'labor_photo', 'labour_image', 'labor_image', 'attendance_photo'] as $key) {
                if ($request->filled($key)) {
                    $data['photo_base64'] = $request->input($key);
                    $data['photo'] = null;
                    break;
                }
            }
        }

        if ($data) {
            $request->merge($data);
        }
    }

    private function labourIdsFromData(array $data): array
    {
        $labourIds = $data['labour_ids'] ?? [$data['labour_id']];

        return collect($labourIds)
            ->filter(fn ($labourId) => filled($labourId))
            ->map(fn ($labourId) => (int) $labourId)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeTimeValue(mixed $time): mixed
    {
        if (! is_string($time)) {
            return $time;
        }

        $time = trim($time);

        if (preg_match('/^(\d{1,2})[.:](\d{2})$/', $time, $matches)) {
            return str_pad($matches[1], 2, '0', STR_PAD_LEFT).':'.$matches[2];
        }

        return $time;
    }

    private function workHoursFromTimes(string $attendanceDate, ?string $inTime, ?string $outTime): ?float
    {
        if (! $inTime || ! $outTime) {
            return null;
        }

        $inAt = Carbon::createFromFormat('Y-m-d H:i', $attendanceDate.' '.$inTime);
        $outAt = Carbon::createFromFormat('Y-m-d H:i', $attendanceDate.' '.$outTime);

        if ($outAt->lessThan($inAt)) {
            $outAt->addDay();
        }

        return round($inAt->diffInMinutes($outAt) / 60, 2);
    }

    private function findEmployeeLabourAttendance(Request $request, int $labourAttendanceId): LabourAttendance
    {
        return LabourAttendance::query()
            ->forCurrentCompany()
            ->where('engineer_user_id', $request->user()->id)
            ->findOrFail($labourAttendanceId);
    }

    private function tenantTable(string $table): string
    {
        $connection = app(\App\Support\Tenant::class)->connectionName();

        return $connection ? $connection.'.'.$table : $table;
    }

    private function storeAttendancePhoto(Request $request, LabourAttendance $attendance): ?string
    {
        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            return $photo->store(self::PHOTO_UPLOAD_DIR . '/' . $request->user()->id . '/' . $attendance->id, 'public');
        }

        $base64Photo = $request->input('photo_base64');

        if (! is_string($base64Photo) || trim($base64Photo) === '') {
            return null;
        }

        return $this->storeBase64AttendancePhoto($base64Photo, $request, $attendance);
    }

    private function storeBase64AttendancePhoto(string $base64Photo, Request $request, LabourAttendance $attendance): string
    {
        $base64Photo = trim($base64Photo);
        $extension = 'jpg';

        if (preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/i', $base64Photo, $matches)) {
            $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
            $base64Photo = $matches[2];
        }

        $base64Photo = preg_replace('/\s+/', '', $base64Photo) ?? '';
        $decodedPhoto = base64_decode($base64Photo, true);

        if ($decodedPhoto === false) {
            throw ValidationException::withMessages([
                'photo' => 'The labour photo must be a valid image.',
            ]);
        }

        if (strlen($decodedPhoto) > self::PHOTO_MAX_UPLOAD_KB * 1024) {
            throw ValidationException::withMessages([
                'photo' => 'The labour photo must not be greater than '.(self::PHOTO_MAX_UPLOAD_KB / 1024).' MB.',
            ]);
        }

        $imageInfo = @getimagesizefromstring($decodedPhoto);

        if (! $imageInfo || ! in_array($imageInfo['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages([
                'photo' => 'The labour photo must be a valid JPG, PNG, or WEBP image.',
            ]);
        }

        $extension = match ($imageInfo['mime']) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => $extension,
        };

        $path = self::PHOTO_UPLOAD_DIR . '/' . $request->user()->id . '/' . $attendance->id . '/photo-' . now()->format('YmdHis') . '.' . $extension;

        Storage::disk('public')->put($path, $decodedPhoto);

        return $path;
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

    private function attendancePhotoPath(LabourAttendance $labourAttendance): ?string
    {
        $attendanceId = (int) $labourAttendance->id;

        if ($attendanceId < 1) {
            return null;
        }

        $patterns = [
            storage_path('app/public/' . self::PHOTO_UPLOAD_DIR . '/*/' . $attendanceId . '/*'),
            storage_path('app/public/labour-attendance/*/' . $attendanceId . '/*'),
            public_path('storage/' . self::PHOTO_UPLOAD_DIR . '/*/' . $attendanceId . '/*'),
            public_path('storage/labour-attendance/*/' . $attendanceId . '/*'),
        ];

        foreach ($patterns as $pattern) {
            $file = collect(glob($pattern) ?: [])
                ->filter(fn (string $path) => is_file($path))
                ->sortDesc()
                ->first();

            if ($file) {
                return $file;
            }
        }

        return null;
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
            preg_replace('#^labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^public/labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^public/storage/labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^storage/labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^storage/app/public/labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^' . preg_quote(self::PHOTO_UPLOAD_DIR, '#') . '/#', 'labour-attendance/', $normalizedPath),
        ])
            ->filter()
            ->map(fn (string $path) => str_replace('\\', '/', $path))
            ->reject(fn (string $path) => str_contains($path, '..'))
            ->unique()
            ->values();
    }

    private function sitePayload(?LabourSite $site): ?array
    {
        if (! $site) {
            return null;
        }

        return [
            'id' => $site->id,
            'name' => $site->name,
            'address' => $site->address,
        ];
    }

    private function contractorPayload(?Contractor $contractor): ?array
    {
        if (! $contractor) {
            return null;
        }

        return [
            'id' => $contractor->id,
            'labour_site_id' => $contractor->labour_site_id,
            'name' => $contractor->name,
            'mobile' => $contractor->mobile,
        ];
    }

    private function labourPayload(?Labour $labour): ?array
    {
        if (! $labour) {
            return null;
        }

        return [
            'id' => $labour->id,
            'contractor_id' => $labour->contractor_id,
            'name' => $labour->name,
            'mobile' => $labour->mobile,
            'labour_code' => $labour->labour_code,
            'trade' => $labour->trade,
        ];
    }

    private function attendancePayload(LabourAttendance $attendance): array
    {
        $attendance->loadMissing(['site', 'contractor', 'labour', 'engineer:id,name,mobile,designation']);

        return [
            'id' => $attendance->id,
            'attendance_date' => $attendance->attendance_date?->toDateString(),
            'date_display' => $attendance->attendance_date?->format('d M Y'),
            'status' => $attendance->status,
            'in_time' => $this->formatAttendanceTime($attendance->in_time),
            'out_time' => $this->formatAttendanceTime($attendance->out_time),
            'work_hours' => $attendance->work_hours,
            'remarks' => $attendance->remarks,
            'photo_path' => $attendance->photo_path,
            'photo_url' => $attendance->photo_path ? route('api.labour-attendances.photo', $attendance) : null,
            'approval_status' => $attendance->approval_status,
            'admin_note' => $attendance->admin_note,
            'reviewed_at' => $attendance->reviewed_at,
            'submitted_at' => $attendance->created_at,
            'site' => $this->sitePayload($attendance->site),
            'contractor' => $this->contractorPayload($attendance->contractor),
            'labour' => $this->labourPayload($attendance->labour),
            'engineer' => [
                'id' => $attendance->engineer?->id,
                'name' => $attendance->engineer?->name,
                'mobile' => $attendance->engineer?->mobile,
                'designation' => $attendance->engineer?->designation,
            ],
        ];
    }

    private function formatAttendanceTime(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        return substr($time, 0, 5);
    }
}
