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
            'mark' => ['nullable', Rule::in(['morning_login', 'evening_out', 'full_day'])],
            'labour_site_id' => ['required_unless:mark,evening_out', 'nullable', Rule::exists($this->tenantTable('labour_sites'), 'id')],
            'contractor_id' => ['required_unless:mark,evening_out', 'nullable', Rule::exists($this->tenantTable('contractors'), 'id')],
            'labour_id' => ['required_without:labour_ids', Rule::exists($this->tenantTable('labours'), 'id')],
            'labour_ids' => ['required_without:labour_id', 'array', 'min:1', 'max:100'],
            'labour_ids.*' => ['required', Rule::exists($this->tenantTable('labours'), 'id')],
            'attendance_date' => ['required', 'date'],
            'status' => ['required_unless:mark,evening_out', 'nullable', Rule::in(LabourAttendance::ATTENDANCE_STATUSES)],
            'in_time' => ['nullable', 'date_format:H:i'],
            'out_time' => ['nullable', 'date_format:H:i'],
            'work_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'photo' => $request->hasFile('photo') ? ['nullable', 'image', 'max:' . self::PHOTO_MAX_UPLOAD_KB] : ['nullable', 'string'],
            'photo_base64' => ['nullable', 'string'],
        ]);

        $mark = $data['mark'] ?? 'full_day';
        $labourIds = $this->labourIdsFromData($data);
        $attendanceDate = Carbon::parse($data['attendance_date'])->toDateString();

        if ($mark === 'evening_out' && blank($data['out_time'] ?? null)) {
            throw ValidationException::withMessages([
                'out_time' => 'The out time is required for evening out.',
            ]);
        }

        if ($mark === 'morning_login' && ($data['status'] ?? null) !== 'absent' && blank($data['in_time'] ?? null)) {
            throw ValidationException::withMessages([
                'in_time' => 'The in time is required for morning login.',
            ]);
        }

        $contractor = isset($data['contractor_id'])
            ? Contractor::query()
                ->forCurrentCompany()
                ->where('id', $data['contractor_id'])
                ->first()
            : null;

        if (! $contractor && $mark !== 'evening_out') {
            throw ValidationException::withMessages([
                'contractor_id' => 'The selected contractor is invalid.',
            ]);
        }

        $laboursQuery = Labour::query()
            ->forCurrentCompany()
            ->whereIn('id', $labourIds);

        if ($contractor) {
            $laboursQuery->where(function ($query) use ($contractor) {
                $query->where('contractor_id', $contractor->id)
                    ->orWhereNull('contractor_id');
            });
        }

        $labours = $laboursQuery->get()
            ->keyBy('id');

        if ($labours->count() !== count($labourIds)) {
            throw ValidationException::withMessages([
                'labour_ids' => $contractor
                    ? 'One or more selected labours are invalid for this contractor.'
                    : 'One or more selected labours are invalid.',
            ]);
        }

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

        $attendances = collect();
        $created = false;

        foreach ($labourIds as $labourId) {
            $attendance = LabourAttendance::query()
                ->forCurrentCompany()
                ->where('labour_id', $labourId)
                ->whereDate('attendance_date', $attendanceDate)
                ->first();

            if ($mark === 'evening_out' && ! $attendance) {
                throw ValidationException::withMessages([
                    'labour_ids' => 'Morning login is not submitted for one or more selected labours.',
                ]);
            }

            $values = $this->attendanceValuesForMark($request, $attendance, $labours->get($labourId), $contractor, $attendanceDate, $data, $mark);

            if ($attendance) {
                $attendance->fill($values);
                $attendance->save();
            } else {
                $attendance = LabourAttendance::query()->create([
                    'company_id' => $request->user()->company_id,
                    'labour_id' => $labourId,
                    'attendance_date' => $attendanceDate,
                    ...$values,
                ]);
            }

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
            'message' => $this->storeSuccessMessage($mark, $created),
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
            foreach (['site_id', 'siteId', 'site', 'labourSiteId', 'laborSiteId', 'leberSiteId', 'selected_site', 'selectedSite'] as $key) {
                if ($request->has($key)) {
                    $data['labour_site_id'] = $request->input($key);
                    break;
                }
            }
        }

        $labourSiteId = $data['labour_site_id'] ?? $request->input('labour_site_id');
        if ($labourSiteId !== null) {
            $data['labour_site_id'] = $this->normalizeIdValue($labourSiteId);
        }

        if (! $request->has('contractor_id')) {
            foreach (['contractorId', 'contractor', 'selected_contractor', 'selectedContractor'] as $key) {
                if ($request->has($key)) {
                    $data['contractor_id'] = $request->input($key);
                    break;
                }
            }
        }

        $contractorId = $data['contractor_id'] ?? $request->input('contractor_id');
        if ($contractorId !== null) {
            $data['contractor_id'] = $this->normalizeIdValue($contractorId);
        }

        if (! $request->has('labour_id')) {
            foreach (['labor_id', 'leber_id', 'labourId', 'laborId', 'leberId'] as $key) {
                if ($request->has($key)) {
                    $data['labour_id'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('labour_ids')) {
            foreach (['labor_ids', 'leber_ids', 'labourIds', 'laborIds', 'leberIds', 'labours', 'labors', 'lebers', 'selected_labours', 'selectedLabours', 'selected_labors', 'selectedLabors', 'selected_lebers', 'selectedLebers'] as $key) {
                if ($request->has($key)) {
                    $data['labour_ids'] = $request->input($key);
                    break;
                }
            }
        }

        $labourId = $data['labour_id'] ?? $request->input('labour_id');
        if ($labourId !== null) {
            $data['labour_id'] = $this->normalizeIdValue($labourId);
        }

        $labourIds = $data['labour_ids'] ?? $request->input('labour_ids');
        if ($labourIds !== null) {
            $data['labour_ids'] = $this->normalizeIdList($labourIds);
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

        if (! $request->has('mark')) {
            foreach (['attendance_mark', 'attendanceMark', 'action', 'type', 'mode'] as $key) {
                if ($request->has($key)) {
                    $data['mark'] = $request->input($key);
                    break;
                }
            }
        }

        $mark = $data['mark'] ?? $request->input('mark');
        if ($mark !== null) {
            $data['mark'] = $this->normalizeMarkValue($mark);
        }

        $status = $data['status'] ?? $request->input('status');
        if ($status !== null) {
            $data['status'] = $this->normalizeStatusValue($status);
        }

        if (! $request->has('work_hours')) {
            foreach (['workHours', 'working_hours', 'workingHours', 'hours'] as $key) {
                if ($request->has($key)) {
                    $data['work_hours'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('in_time')) {
            foreach (['inTime', 'start_time', 'startTime', 'login_time', 'loginTime', 'morning_login_time', 'morningLoginTime'] as $key) {
                if ($request->has($key)) {
                    $data['in_time'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('out_time')) {
            foreach (['outTime', 'end_time', 'endTime', 'logout_time', 'logoutTime', 'evening_out_time', 'eveningOutTime'] as $key) {
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
            foreach (['image', 'labour_photo', 'labor_photo', 'leber_photo', 'labour_image', 'labor_image', 'leber_image', 'attendance_photo'] as $key) {
                if ($request->hasFile($key)) {
                    $request->files->set('photo', $request->file($key));
                    break;
                }
            }
        }

        if (! $request->hasFile('photo') && ! $request->filled('photo_base64')) {
            foreach (['photo', 'image', 'labour_photo', 'labor_photo', 'leber_photo', 'labour_image', 'labor_image', 'leber_image', 'attendance_photo'] as $key) {
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

    private function attendanceValuesForMark(
        Request $request,
        ?LabourAttendance $attendance,
        Labour $labour,
        ?Contractor $contractor,
        string $attendanceDate,
        array $data,
        string $mark
    ): array {
        $status = $data['status'] ?? $attendance?->status ?? 'present';
        $siteId = $data['labour_site_id'] ?? $attendance?->labour_site_id ?? $contractor?->labour_site_id;
        $contractorId = $contractor?->id ?? $attendance?->contractor_id ?? $labour->contractor_id;

        if (! $siteId || ! $contractorId) {
            throw ValidationException::withMessages([
                'labour_site_id' => 'Site and contractor details are required for this labour attendance.',
            ]);
        }

        $inTime = $attendance?->in_time ? $this->formatAttendanceTime($attendance->in_time) : null;
        $outTime = $attendance?->out_time ? $this->formatAttendanceTime($attendance->out_time) : null;

        if ($mark === 'evening_out') {
            $outTime = $data['out_time'] ?? $outTime;
        } elseif ($mark === 'morning_login') {
            $inTime = $data['in_time'] ?? $inTime;
            $outTime = null;
        } else {
            $inTime = array_key_exists('in_time', $data) ? ($data['in_time'] ?? null) : $inTime;
            $outTime = array_key_exists('out_time', $data) ? ($data['out_time'] ?? null) : $outTime;
        }

        if ($status === 'absent') {
            $inTime = null;
            $outTime = null;
        }

        $workHours = $this->workHoursFromTimes($attendanceDate, $inTime, $outTime);
        $workHours ??= $data['work_hours'] ?? ($mark === 'morning_login' ? null : $attendance?->work_hours);

        return [
            'engineer_user_id' => $request->user()->id,
            'labour_site_id' => $siteId,
            'contractor_id' => $contractorId,
            'status' => $status,
            'in_time' => $inTime,
            'out_time' => $outTime,
            'work_hours' => $workHours,
            'remarks' => array_key_exists('remarks', $data) ? ($data['remarks'] ?? null) : $attendance?->remarks,
            'approval_status' => 'pending',
            'admin_note' => null,
            'reviewed_at' => null,
        ];
    }

    private function storeSuccessMessage(string $mark, bool $created): string
    {
        return match ($mark) {
            'morning_login' => $created
                ? 'Morning labour login submitted successfully.'
                : 'Morning labour login updated successfully.',
            'evening_out' => 'Evening labour out submitted and sent for approval.',
            default => $created
                ? 'Labour attendance submitted successfully.'
                : 'Labour attendance updated and sent for approval.',
        };
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

    private function normalizeIdValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value['id']
                ?? $value['labour_id']
                ?? $value['labor_id']
                ?? $value['leber_id']
                ?? $value['contractor_id']
                ?? $value['site_id']
                ?? $value['value']
                ?? $value;
        }

        return $value;
    }

    private function normalizeMarkValue(mixed $mark): mixed
    {
        if (! is_string($mark)) {
            return $mark;
        }

        $normalized = strtolower(trim($mark));
        $normalized = preg_replace('/[\s-]+/', '_', $normalized) ?? $normalized;

        return match ($normalized) {
            'morning', 'morning_in', 'morning_login', 'login', 'in', 'check_in', 'checkin' => 'morning_login',
            'evening', 'evening_out', 'evening_logout', 'logout', 'out', 'out_selected', 'check_out', 'checkout' => 'evening_out',
            'full', 'full_day', 'submit' => 'full_day',
            default => $normalized,
        };
    }

    private function normalizeIdList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                $value = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->map(fn ($labourId) => $this->normalizeIdValue($labourId))
            ->filter(fn ($labourId) => filled($labourId))
            ->values()
            ->all();
    }

    private function normalizeStatusValue(mixed $status): mixed
    {
        if (! is_string($status)) {
            return $status;
        }

        $normalized = strtolower(trim($status));
        $normalized = preg_replace('/[\s-]+/', '_', $normalized) ?? $normalized;

        return match ($normalized) {
            'present', 'p' => 'present',
            'absent', 'a' => 'absent',
            'half_day', 'halfday', 'half' => 'half_day',
            default => $normalized,
        };
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

        if (! $photo instanceof UploadedFile) {
            foreach (['image', 'labour_photo', 'labor_photo', 'leber_photo', 'labour_image', 'labor_image', 'leber_image', 'attendance_photo'] as $key) {
                $aliasPhoto = $request->file($key);

                if ($aliasPhoto instanceof UploadedFile) {
                    $photo = $aliasPhoto;
                    break;
                }
            }
        }

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
            'attendance_stage' => $this->attendanceStage($attendance),
            'is_out_pending' => $this->isOutPending($attendance),
            'can_submit_out' => $this->isOutPending($attendance),
            'can_be_approved' => ! $this->isOutPending($attendance),
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

    private function attendanceStage(LabourAttendance $attendance): string
    {
        return $this->isOutPending($attendance) ? 'out_pending' : 'complete';
    }

    private function isOutPending(LabourAttendance $attendance): bool
    {
        return in_array($attendance->status, ['present', 'half_day'], true)
            && filled($attendance->in_time)
            && blank($attendance->out_time);
    }
}
