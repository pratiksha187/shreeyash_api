<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
       
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $validator->validated();
        $user = User::query()->where('mobile', $credentials['mobile'])->first();
 
        if (
            ! $user
            || ! Hash::isHashed($user->password)
            || ! Hash::check($credentials['password'], $user->password)
        ) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
                'errors' => [
                    'mobile' => ['The provided credentials are incorrect.'],
                ],
            ], 422);
        }

        $token = Str::random(80);
        $user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return response()->json([
            'message' => 'Login successful.',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => $user,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        $todayAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', today())
            ->first();

        return response()->json([
            'message' => 'Profile details fetched successfully.',
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'gender' => $user->gender,
                'marital_status' => $user->marital_status,
                'date_of_birth' => $user->date_of_birth,
                'join_date' => $user->join_date,
                'confirmation_date' => $user->confirmation_date,
                'probation_months' => $user->probation_months,
                'aadhaar_number' => $user->aadhaar_number,
                'hours_per_day' => $user->hours_per_day,
                'days_per_week' => $user->days_per_week,
                'salary' => $user->salary,
                'insurance' => $user->insurance,
                'pt' => $user->pt,
                'advance' => $user->advance,
                'pf' => $user->pf,
                'designation' => $user->designation,
                'joined_at' => $user->created_at,
            ],
            'today_attendance' => $todayAttendance,
        ]);
    }
}
