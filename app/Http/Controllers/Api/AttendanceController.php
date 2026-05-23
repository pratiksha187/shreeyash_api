<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function clockIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attendance = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', today())
            ->first();

        if ($attendance && $attendance->check_in_at) {
            return response()->json([
                'message' => 'You have already clocked in today.',
                'attendance' => $attendance,
            ], 409);
        }

        $attendance = Attendance::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'attendance_date' => today()->toDateString(),
            ],
            array_merge($validator->validated(), [
                'status' => 'present',
                'check_in_at' => now(),
                'check_out_at' => null,
            ])
        );

        return response()->json([
            'message' => 'Clock in successful.',
            'attendance' => $attendance,
        ], 201);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attendance = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', today())
            ->first();

        if (! $attendance || ! $attendance->check_in_at) {
            return response()->json([
                'message' => 'Please clock in before clocking out.',
            ], 409);
        }

        if ($attendance->check_out_at) {
            return response()->json([
                'message' => 'You have already clocked out today.',
                'attendance' => $attendance,
            ], 409);
        }

        $attendance->fill(array_merge($validator->validated(), [
            'check_out_at' => now(),
        ]));
        $attendance->save();

        return response()->json([
            'message' => 'Clock out successful.',
            'attendance' => $attendance,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $attendance = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', today())
            ->first();

        return response()->json([
            'user' => $request->user(),
            'attendance' => $attendance,
        ]);
    }

    public function dailyReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filters = $validator->validated();
        $fromDate = isset($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : today()->toDateString();

        $attendances = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->orderByDesc('attendance_date')
            ->get();

        return response()->json([
            'message' => 'Daily attendance report fetched successfully.',
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => [
                'total_days' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'leave' => $attendances->where('status', 'leave')->count(),
                'half_day' => $attendances->where('status', 'half_day')->count(),
            ],
            'attendances' => $attendances,
        ]);
    }

    public function applyLeave(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_date' => ['required', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $fromDate = Carbon::parse($data['from_date'])->toDateString();
        $toDate = isset($data['to_date'])
            ? Carbon::parse($data['to_date'])->toDateString()
            : $fromDate;

        $dates = collect(CarbonPeriod::create($fromDate, $toDate))
            ->map(fn (Carbon $date) => $date->toDateString());

        $existingWorkedDays = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('attendance_date', $dates)
            ->where(function ($query) {
                $query->whereNotNull('check_in_at')
                    ->orWhereNotNull('check_out_at');
            })
            ->pluck('attendance_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values();

        if ($existingWorkedDays->isNotEmpty()) {
            return response()->json([
                'message' => 'Leave cannot be applied for dates where attendance is already marked.',
                'dates' => $existingWorkedDays,
            ], 409);
        }

        $attendances = $dates->map(function (string $date) use ($request, $data) {
            return Attendance::query()->updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'attendance_date' => $date,
                ],
                [
                    'status' => 'leave',
                    'check_in_at' => null,
                    'check_out_at' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'remarks' => $data['remarks'] ?? null,
                ]
            );
        });

        return response()->json([
            'message' => 'Leave applied successfully.',
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'attendances' => $attendances,
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['present', 'absent', 'leave', 'half_day'])],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date', 'after_or_equal:check_in_at'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $attendance = Attendance::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'attendance_date' => today()->toDateString(),
            ],
            $data
        );

        return response()->json([
            'message' => 'Attendance updated successfully.',
            'attendance' => $attendance,
        ]);
    }
}
