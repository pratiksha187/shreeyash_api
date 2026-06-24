<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = Attendance::query()
            ->where('status', 'leave')
            ->orderByDesc('date');

        if ($request->filled('employee_id')) {
            $query->where('user_id', $request->input('employee_id'));
        }

        if ($request->filled('month')) {
            $query->where('date', 'like', $request->input('month').'%');
        }

        $leaves = $query->with('user')->paginate(20)->appends($request->query());

        return view('admin.leave-requests.index', [
            'leaves' => $leaves,
            'employees' => \App\Models\User::query()->employees()->orderBy('name')->get(),
            'selectedEmployeeId' => $request->input('employee_id'),
            'selectedMonth' => $request->input('month'),
        ]);
    }
}
