<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MissedAttendanceRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MissedAttendanceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(MissedAttendanceRequest::STATUSES)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $requests = MissedAttendanceRequest::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('attendance_date')
            ->latest()
            ->limit($filters['limit'] ?? 30)
            ->get()
            ->map(fn (MissedAttendanceRequest $missedRequest) => $this->requestPayload($missedRequest));

        return response()->json([
            'message' => 'Missed attendance requests fetched successfully.',
            'missed_requests' => $requests,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeInput($request);

        $data = $request->validate([
            'attendance_date' => ['required', 'date'],
            'request_for' => ['required', Rule::in(MissedAttendanceRequest::REQUEST_TYPES)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $existingPending = MissedAttendanceRequest::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', Carbon::parse($data['attendance_date'])->toDateString())
            ->where('request_for', $data['request_for'])
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'message' => 'A pending missed attendance request already exists for this date and type.',
                'missed_request' => $this->requestPayload($existingPending),
            ], 409);
        }

        $missedRequest = MissedAttendanceRequest::query()->create([
            'user_id' => $request->user()->id,
            'attendance_date' => Carbon::parse($data['attendance_date'])->toDateString(),
            'request_for' => $data['request_for'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        $missedRequest->load('user:id,name,mobile,designation');

        return response()->json([
            'message' => 'Missed attendance request submitted successfully.',
            'missed_request' => $this->requestPayload($missedRequest),
        ], 201);
    }

    public function show(Request $request, int $missedAttendanceRequest): JsonResponse
    {
        $missedAttendanceRequest = MissedAttendanceRequest::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->findOrFail($missedAttendanceRequest);

        $missedAttendanceRequest->load('user:id,name,mobile,designation');

        return response()->json([
            'message' => 'Missed attendance request fetched successfully.',
            'missed_request' => $this->requestPayload($missedAttendanceRequest),
        ]);
    }

    private function normalizeInput(Request $request): void
    {
        $data = [];

        if (! $request->has('attendance_date')) {
            foreach (['date', 'attendanceDate', 'request_date'] as $key) {
                if ($request->has($key)) {
                    $data['attendance_date'] = $request->input($key);
                    break;
                }
            }
        }

        if (! $request->has('request_for')) {
            foreach (['requestFor', 'request_type', 'type', 'for'] as $key) {
                if ($request->has($key)) {
                    $data['request_for'] = $request->input($key);
                    break;
                }
            }
        }

        if (isset($data['request_for']) || $request->has('request_for')) {
            $value = $data['request_for'] ?? $request->input('request_for');
            $normalized = strtolower(str_replace([' ', '-'], '_', trim((string) $value)));

            $data['request_for'] = match ($normalized) {
                'clockin', 'clock_in', 'in' => 'clock_in',
                'clockout', 'clock_out', 'out' => 'clock_out',
                'fullday', 'full_day', 'full' => 'full_day',
                default => $normalized,
            };
        }

        if (! $request->has('reason')) {
            foreach (['message', 'description', 'details', 'remark', 'remarks'] as $key) {
                if ($request->has($key)) {
                    $data['reason'] = $request->input($key);
                    break;
                }
            }
        }

        if ($data) {
            $request->merge($data);
        }
    }

    private function requestPayload(MissedAttendanceRequest $missedRequest): array
    {
        $missedRequest->loadMissing('user:id,name,mobile,designation');

        return [
            'id' => $missedRequest->id,
            'attendance_date' => $missedRequest->attendance_date?->toDateString(),
            'date_display' => $missedRequest->attendance_date?->format('d M Y'),
            'request_for' => $missedRequest->request_for,
            'request_for_label' => str_replace('_', ' ', $missedRequest->request_for),
            'reason' => $missedRequest->reason,
            'status' => $missedRequest->status,
            'admin_note' => $missedRequest->admin_note,
            'reviewed_at' => $missedRequest->reviewed_at,
            'submitted_at' => $missedRequest->created_at,
            'employee' => [
                'id' => $missedRequest->user?->id,
                'name' => $missedRequest->user?->name,
                'mobile' => $missedRequest->user?->mobile,
                'designation' => $missedRequest->user?->designation,
            ],
        ];
    }
}
