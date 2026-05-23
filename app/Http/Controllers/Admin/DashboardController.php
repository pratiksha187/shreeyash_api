<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
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
            'recentEmployees' => User::query()
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
