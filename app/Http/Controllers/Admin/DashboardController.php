<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard.index', [
            'totalEmployees' => User::query()->count(),
            'todayPresent' => Attendance::query()
                ->whereDate('attendance_date', today())
                ->where('status', 'present')
                ->count(),
            'todayLeave' => Attendance::query()
                ->whereDate('attendance_date', today())
                ->where('status', 'leave')
                ->count(),
            'todayAbsent' => Attendance::query()
                ->whereDate('attendance_date', today())
                ->where('status', 'absent')
                ->count(),
            'totalVehicles' => Vehicle::query()->count(),
            'todayVehicles' => VehicleLog::query()
                ->whereDate('in_at', today())
                ->count(),
            'vehiclesInside' => VehicleLog::query()
                ->whereNull('out_at')
                ->count(),
            'recentEmployees' => User::query()
                ->latest()
                ->take(5)
                ->get(),
            'recentVehicleLogs' => VehicleLog::query()
                ->with('vehicle')
                ->latest('in_at')
                ->take(5)
                ->get(),
        ]);
    }
}
