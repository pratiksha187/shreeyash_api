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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LabourAttendanceController extends Controller
{
    public function sites(): JsonResponse
    {
        $sites = LabourSite::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (LabourSite $site) => $this->sitePayload($site));

        return response()->json([
            'message' => 'Labour sites fetched successfully.',
            'sites' => $sites,
        ]);
    }

    public function contractors(LabourSite $labourSite): JsonResponse
    {
        $contractors = $labourSite->contractors()
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

    public function labours(Contractor $contractor): JsonResponse
    {
        $contractor->load('site');

        $labours = $contractor->labours()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Labour $labour) => $this->labourPayload($labour));

        return response()->json([
            'message' => 'Labours fetched successfully.',
            'site' => $this->sitePayload($contractor->site),
            'contractor' => $this->contractorPayload($contractor),
            'labours' => $labours,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'labour_id' => ['nullable', 'exists:labours,id'],
            'approval_status' => ['nullable', Rule::in(LabourAttendance::APPROVAL_STATUSES)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = LabourAttendance::query()
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
            'labour_site_id' => ['required', 'exists:labour_sites,id'],
            'contractor_id' => ['required', 'exists:contractors,id'],
            'labour_id' => ['required', 'exists:labours,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(LabourAttendance::ATTENDANCE_STATUSES)],
            'work_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $contractor = Contractor::query()
            ->where('id', $data['contractor_id'])
            ->where('labour_site_id', $data['labour_site_id'])
            ->first();

        if (! $contractor) {
            throw ValidationException::withMessages([
                'contractor_id' => 'The selected contractor does not belong to this site.',
            ]);
        }

        $labour = Labour::query()
            ->where('id', $data['labour_id'])
            ->where('contractor_id', $contractor->id)
            ->first();

        if (! $labour) {
            throw ValidationException::withMessages([
                'labour_id' => 'The selected labour does not belong to this contractor.',
            ]);
        }

        $attendanceDate = Carbon::parse($data['attendance_date'])->toDateString();
        $existingAttendance = LabourAttendance::query()
            ->where('labour_id', $labour->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        if ($existingAttendance && $existingAttendance->approval_status === 'approved') {
            return response()->json([
                'message' => 'Labour attendance is already approved for this date.',
                'labour_attendance' => $this->attendancePayload($existingAttendance->load(['site', 'contractor', 'labour', 'engineer'])),
            ], 409);
        }

        $attendance = LabourAttendance::query()->updateOrCreate(
            [
                'labour_id' => $labour->id,
                'attendance_date' => $attendanceDate,
            ],
            [
                'engineer_user_id' => $request->user()->id,
                'labour_site_id' => $data['labour_site_id'],
                'contractor_id' => $contractor->id,
                'status' => $data['status'],
                'work_hours' => $data['work_hours'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'approval_status' => 'pending',
                'admin_note' => null,
                'reviewed_at' => null,
            ]
        );

        $attendance->load(['site', 'contractor', 'labour', 'engineer:id,name,mobile,designation']);

        return response()->json([
            'message' => $attendance->wasRecentlyCreated
                ? 'Labour attendance submitted successfully.'
                : 'Labour attendance updated and sent for approval.',
            'labour_attendance' => $this->attendancePayload($attendance),
        ], $attendance->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request, LabourAttendance $labourAttendance): JsonResponse
    {
        if ($labourAttendance->engineer_user_id !== $request->user()->id) {
            abort(404);
        }

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

        if ($data) {
            $request->merge($data);
        }
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
            'work_hours' => $attendance->work_hours,
            'remarks' => $attendance->remarks,
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
}
