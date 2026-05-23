<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = User::query()
            ->latest()
            ->paginate(10);

        return view('admin.employees.index', [
            'employees' => $employees,
        ]);
    }

    public function create(): View
    {
        return view('admin.employees.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'gender' => ['nullable', 'string', 'max:20'],
            'marital_status' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'join_date' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'probation_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'aadhaar_number' => ['nullable', 'string', 'max:20'],
            'hours_per_day' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'days_per_week' => ['nullable', 'integer', 'min:0', 'max:7'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'insurance' => ['nullable', 'numeric', 'min:0'],
            'pt' => ['nullable', 'numeric', 'min:0'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'pf' => ['nullable', 'numeric', 'min:0'],
            'designation' => ['nullable', 'string', 'max:255'],
        ]);

        unset($data['password_confirmation']);
        $data['password'] = Hash::make($data['password']);

        User::query()->create($data);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Employee added successfully.');
    }
}
