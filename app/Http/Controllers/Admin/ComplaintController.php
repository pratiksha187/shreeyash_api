<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'user_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', Rule::in(Complaint::STATUSES)],
        ]);

        $fromDate = isset($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : today()->toDateString();
        $userId = $filters['user_id'] ?? null;
        $status = $filters['status'] ?? null;

        $baseQuery = Complaint::query()
            ->whereBetween('created_at', [
                Carbon::parse($fromDate)->startOfDay(),
                Carbon::parse($toDate)->endOfDay(),
            ])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($status, fn ($query) => $query->where('status', $status));

        return view('admin.complaints.index', [
            'complaints' => (clone $baseQuery)
                ->with('user:id,name,mobile,designation')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'employees' => User::query()->orderBy('name')->get(['id', 'name', 'mobile']),
            'statuses' => Complaint::STATUSES,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedUserId' => $userId,
            'selectedStatus' => $status,
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'open' => (clone $baseQuery)->where('status', 'open')->count(),
                'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
                'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
            ],
        ]);
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Complaint::STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $complaint->fill([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'resolved_at' => in_array($data['status'], ['resolved', 'closed'], true)
                ? ($complaint->resolved_at ?? now())
                : null,
        ]);
        $complaint->save();

        return back()->with('success', 'Complaint updated successfully.');
    }
}
