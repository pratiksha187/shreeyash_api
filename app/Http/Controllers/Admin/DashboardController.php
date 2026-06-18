<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Challan;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLog;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::now(Attendance::LOCAL_TIMEZONE)->toDateString();

        return view('admin.dashboard.index', [
            'totalEmployees' => User::query()->forCurrentCompany()->employees()->count(),
            'todayPresent' => Attendance::query()
                ->forCurrentCompany()
                ->whereDate('attendance_date', $today)
                ->where('status', 'present')
                ->count(),
            'todayLeave' => Attendance::query()
                ->forCurrentCompany()
                ->whereDate('attendance_date', $today)
                ->where('status', 'leave')
                ->count(),
            'todayAbsent' => Attendance::query()
                ->forCurrentCompany()
                ->whereDate('attendance_date', $today)
                ->where('status', 'absent')
                ->count(),
            'todayPresentEmployees' => Attendance::query()
                ->forCurrentCompany()
                ->with('user:id,name,email,mobile,designation')
                ->whereDate('attendance_date', $today)
                ->where('status', 'present')
                ->orderBy('check_in_at')
                ->paginate(10, ['*'], 'present_page')
                ->withQueryString(),
            'totalVehicles' => Vehicle::query()->forCurrentCompany()->count(),
            'totalChallans' => Challan::query()->forCurrentCompany()->count(),
            'todayVehicles' => VehicleLog::query()
                ->forCurrentCompany()
                ->whereDate('in_at', today())
                ->count(),
            'vehiclesInside' => VehicleLog::query()
                ->forCurrentCompany()
                ->whereNull('out_at')
                ->count(),
            'recentEmployees' => User::query()
                ->forCurrentCompany()
                ->employees()
                ->latest()
                ->take(5)
                ->get(),
            'recentVehicleLogs' => VehicleLog::query()
                ->forCurrentCompany()
                ->with('vehicle')
                ->latest('in_at')
                ->take(5)
                ->get(),
            'recentChallans' => Challan::query()
                ->forCurrentCompany()
                ->with('user:id,name,mobile')
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
