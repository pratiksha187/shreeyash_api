<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\LabourSite;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LabourAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'labour_site_id' => ['nullable', 'exists:labour_sites,id'],
            'contractor_id' => ['nullable', 'exists:contractors,id'],
            'labour_id' => ['nullable', 'exists:labours,id'],
            'engineer_user_id' => ['nullable', 'exists:users,id'],
            'approval_status' => ['nullable', Rule::in(LabourAttendance::APPROVAL_STATUSES)],
        ]);

        $fromDate = isset($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $toDate = isset($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : today()->toDateString();

        $baseQuery = LabourAttendance::query()
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->when(isset($filters['labour_site_id']), fn ($query) => $query->where('labour_site_id', $filters['labour_site_id']))
            ->when(isset($filters['contractor_id']), fn ($query) => $query->where('contractor_id', $filters['contractor_id']))
            ->when(isset($filters['labour_id']), fn ($query) => $query->where('labour_id', $filters['labour_id']))
            ->when(isset($filters['engineer_user_id']), fn ($query) => $query->where('engineer_user_id', $filters['engineer_user_id']))
            ->when(isset($filters['approval_status']), fn ($query) => $query->where('approval_status', $filters['approval_status']));

        return view('admin.labour-attendance.index', [
            'attendances' => (clone $baseQuery)
                ->with(['site', 'contractor', 'labour', 'engineer:id,name,mobile,designation'])
                ->orderByDesc('attendance_date')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'sites' => LabourSite::query()->with('contractors.labours')->orderBy('name')->get(),
            'contractors' => Contractor::query()->with('site')->orderBy('name')->get(),
            'labours' => Labour::query()->with('contractor.site')->orderBy('name')->get(),
            'engineers' => User::query()->orderBy('name')->get(['id', 'name', 'mobile']),
            'attendanceStatuses' => LabourAttendance::ATTENDANCE_STATUSES,
            'approvalStatuses' => LabourAttendance::APPROVAL_STATUSES,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedSiteId' => $filters['labour_site_id'] ?? null,
            'selectedContractorId' => $filters['contractor_id'] ?? null,
            'selectedLabourId' => $filters['labour_id'] ?? null,
            'selectedEngineerId' => $filters['engineer_user_id'] ?? null,
            'selectedApprovalStatus' => $filters['approval_status'] ?? null,
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->where('approval_status', 'pending')->count(),
                'approved' => (clone $baseQuery)->where('approval_status', 'approved')->count(),
                'rejected' => (clone $baseQuery)->where('approval_status', 'rejected')->count(),
            ],
        ]);
    }

    public function storeSite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:labour_sites,name'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        LabourSite::query()->create($data);

        return back()->with('success', 'Site added successfully.');
    }

    public function storeContractor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'labour_site_id' => ['required', 'exists:labour_sites,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contractors', 'name')->where('labour_site_id', $request->input('labour_site_id')),
            ],
            'mobile' => ['nullable', 'string', 'max:20'],
        ]);

        Contractor::query()->create($data);

        return back()->with('success', 'Contractor added successfully.');
    }

    public function storeLabour(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contractor_id' => ['required', 'exists:contractors,id'],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'labour_code' => ['nullable', 'string', 'max:50'],
            'trade' => ['nullable', 'string', 'max:100'],
        ]);

        Labour::query()->create($data);

        return back()->with('success', 'Labour added successfully.');
    }

    public function update(Request $request, LabourAttendance $labourAttendance): RedirectResponse
    {
        $data = $request->validate([
            'approval_status' => ['required', Rule::in(LabourAttendance::APPROVAL_STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $labourAttendance->fill([
            'approval_status' => $data['approval_status'],
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_at' => $data['approval_status'] === 'pending' ? null : now(),
        ]);
        $labourAttendance->save();

        return back()->with('success', 'Labour attendance updated successfully.');
    }
}
