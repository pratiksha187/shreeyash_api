<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\LabourSite;
use App\Models\User;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LabourAttendanceController extends Controller
{
    private const PHOTO_UPLOAD_DIR = 'leber_image';

    public function master(): View
    {
        return view('admin.labour-attendance.master', $this->masterData());
    }

    public function sites(): View
    {
        return view('admin.labour-attendance.sites', [
            'sites' => LabourSite::query()
                ->forCurrentCompany()
                ->withCount(['contractors', 'labourAttendances'])
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function contractors(): View
    {
        $this->ensureDecoupledLabourMasterSchema();

        return view('admin.labour-attendance.contractors', [
            'contractors' => Contractor::query()
                ->forCurrentCompany()
                ->withCount(['labours', 'labourAttendances'])
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function labours(Request $request): View
    {
        $this->ensureDecoupledLabourMasterSchema();
        $contractorsTable = $this->tenantTable('contractors');
        $filters = $request->validate([
            'contractor_id' => ['nullable', Rule::exists($contractorsTable, 'id')->where('company_id', app(Tenant::class)->id())],
        ]);

        return view('admin.labour-attendance.labours', [
            'labours' => Labour::query()
                ->forCurrentCompany()
                ->with('contractor:id,name')
                ->withCount('labourAttendances')
                ->when(isset($filters['contractor_id']), fn ($query) => $query->where('contractor_id', $filters['contractor_id']))
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            'contractors' => Contractor::query()
                ->forCurrentCompany()
                ->orderBy('name')
                ->get(['id', 'name']),
            'selectedContractorId' => $filters['contractor_id'] ?? null,
        ]);
    }

    public function editSite(int $labourSite): View
    {
        $labourSite = $this->findSite($labourSite);

        return view('admin.labour-attendance.edit-site', [
            'site' => $labourSite,
        ]);
    }

    public function editContractor(int $contractor): View
    {
        $this->ensureDecoupledLabourMasterSchema();
        $contractor = $this->findContractor($contractor);

        return view('admin.labour-attendance.edit-contractor', [
            'contractor' => $contractor,
        ]);
    }

    public function editLabour(int $labour): View
    {
        $this->ensureDecoupledLabourMasterSchema();
        $labour = $this->findLabour($labour);

        return view('admin.labour-attendance.edit-labour', [
            'labour' => $labour,
            'contractors' => Contractor::query()
                ->forCurrentCompany()
                ->where(function ($query) use ($labour) {
                    $query->where('is_active', true)
                        ->orWhere('id', $labour->contractor_id);
                })
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

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
            ->forCurrentCompany()
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
            ...$this->masterData(),
            'engineers' => User::query()->forCurrentCompany()->employees()->orderBy('name')->get(['id', 'name', 'mobile']),
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
                'out_pending' => (clone $baseQuery)
                    ->whereIn('status', ['present', 'half_day'])
                    ->whereNotNull('in_time')
                    ->whereNull('out_time')
                    ->count(),
                'approved' => (clone $baseQuery)->where('approval_status', 'approved')->count(),
                'rejected' => (clone $baseQuery)->where('approval_status', 'rejected')->count(),
            ],
        ]);
    }

    public function storeSite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($this->tenantTable('labour_sites'), 'name')
                    ->where('company_id', app(Tenant::class)->id()),
            ],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        LabourSite::query()->create($data);

        return redirect()->route('admin.labour-sites.index')->with('success', 'Site added successfully.');
    }

    public function updateSite(Request $request, int $labourSite): RedirectResponse
    {
        $labourSite = $this->findSite($labourSite);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($this->tenantTable('labour_sites'), 'name')
                    ->where('company_id', app(Tenant::class)->id())
                    ->ignore($labourSite->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $labourSite->update($data);

        return redirect()->route('admin.labour-sites.index')->with('success', 'Site updated successfully.');
    }

    public function destroySite(int $labourSite): RedirectResponse
    {
        $labourSite = $this->findSite($labourSite);

        if ($labourSite->contractors()->exists() || $labourSite->labourAttendances()->exists()) {
            return back()->with('error', 'This site is already used. Mark it inactive instead of deleting it.');
        }

        $labourSite->delete();

        return redirect()->route('admin.labour-sites.index')->with('success', 'Site deleted successfully.');
    }

    public function storeContractor(Request $request): RedirectResponse
    {
        $this->ensureDecoupledLabourMasterSchema();

        $data = $request->validate($this->contractorRules());

        Contractor::query()->create($data);

        return redirect()->route('admin.contractors.index')->with('success', 'Contractor added successfully.');
    }

    public function updateContractor(Request $request, int $contractor): RedirectResponse
    {
        $this->ensureDecoupledLabourMasterSchema();
        $contractor = $this->findContractor($contractor);

        $data = $request->validate($this->contractorRules(includeStatus: true));

        $contractor->update($data);

        return redirect()->route('admin.contractors.index')->with('success', 'Contractor updated successfully.');
    }

    public function destroyContractor(int $contractor): RedirectResponse
    {
        $this->ensureDecoupledLabourMasterSchema();
        $contractor = $this->findContractor($contractor);

        if ($contractor->labours()->exists() || $contractor->labourAttendances()->exists()) {
            return back()->with('error', 'This contractor is already used. Mark it inactive instead of deleting it.');
        }

        $contractor->delete();

        return redirect()->route('admin.contractors.index')->with('success', 'Contractor deleted successfully.');
    }

    public function storeLabour(Request $request): RedirectResponse
    {
        $this->ensureDecoupledLabourMasterSchema();
        $contractorsTable = $this->tenantTable('contractors');

        $data = $request->validate([
            'contractor_id' => ['nullable', Rule::exists($contractorsTable, 'id')->where('company_id', app(Tenant::class)->id())],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'labour_code' => ['nullable', 'string', 'max:50'],
            'trade' => ['nullable', 'string', 'max:100'],
            'labour_type' => ['required', Rule::in(['permanent', 'daily_wage'])],
            'work_category' => ['nullable', 'string', 'max:120'],
            'daily_wage_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        Labour::query()->create($data);

        return redirect()->route('admin.labours.index')->with('success', 'Labour added successfully.');
    }

    public function updateLabour(Request $request, int $labour): RedirectResponse
    {
        $this->ensureDecoupledLabourMasterSchema();
        $labour = $this->findLabour($labour);
        $contractorsTable = $this->tenantTable('contractors');

        $data = $request->validate([
            'contractor_id' => ['nullable', Rule::exists($contractorsTable, 'id')->where('company_id', app(Tenant::class)->id())],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'labour_code' => ['nullable', 'string', 'max:50'],
            'trade' => ['nullable', 'string', 'max:100'],
            'labour_type' => ['required', Rule::in(['permanent', 'daily_wage'])],
            'work_category' => ['nullable', 'string', 'max:120'],
            'daily_wage_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active' => ['required', 'boolean'],
        ]);

        $labour->update($data);

        return redirect()->route('admin.labours.index')->with('success', 'Labour updated successfully.');
    }

    public function destroyLabour(int $labour): RedirectResponse
    {
        $this->ensureDecoupledLabourMasterSchema();
        $labour = $this->findLabour($labour);

        if ($labour->labourAttendances()->exists()) {
            return back()->with('error', 'This labour is already used in attendance. Mark it inactive instead of deleting it.');
        }

        $labour->delete();

        return redirect()->route('admin.labours.index')->with('success', 'Labour deleted successfully.');
    }

    private function ensureDecoupledLabourMasterSchema(): void
    {
        $connection = DB::connection(app(Tenant::class)->connectionName());

        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $this->tryDatabaseChange(fn () => $connection->statement('ALTER TABLE contractors DROP FOREIGN KEY contractors_site_fk'));
        $this->tryDatabaseChange(fn () => $connection->statement('ALTER TABLE contractors DROP INDEX contractors_site_name_unique'));
        $this->tryDatabaseChange(fn () => $connection->statement('ALTER TABLE contractors MODIFY labour_site_id BIGINT UNSIGNED NULL'));
        $this->tryDatabaseChange(fn () => $connection->statement('ALTER TABLE labours DROP FOREIGN KEY labours_contractor_fk'));
        $this->tryDatabaseChange(fn () => $connection->statement('ALTER TABLE labours DROP INDEX labours_contractor_name_index'));
        $this->tryDatabaseChange(fn () => $connection->statement('ALTER TABLE labours MODIFY contractor_id BIGINT UNSIGNED NULL'));
    }

    private function tryDatabaseChange(callable $callback): void
    {
        try {
            $callback();
        } catch (QueryException) {
        }
    }

    public function update(Request $request, LabourAttendance $labourAttendance): RedirectResponse
    {
        $data = $request->validate([
            'approval_status' => ['required', Rule::in(LabourAttendance::APPROVAL_STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['approval_status'] === 'approved' && $this->isOutPending($labourAttendance)) {
            return back()->withErrors([
                'approval_status' => 'Evening out time is pending for this labour attendance. Please approve after out is submitted.',
            ]);
        }

        $labourAttendance->fill([
            'approval_status' => $data['approval_status'],
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_at' => $data['approval_status'] === 'pending' ? null : now(),
        ]);
        $labourAttendance->save();

        return back()->with('success', 'Labour attendance updated successfully.');
    }

    private function masterData(): array
    {
        return [
            'sites' => LabourSite::query()->forCurrentCompany()->orderBy('name')->get(),
            'contractors' => Contractor::query()->forCurrentCompany()->orderBy('name')->get(),
            'labours' => Labour::query()->forCurrentCompany()->orderBy('name')->get(),
        ];
    }

    private function findSite(int $labourSite): LabourSite
    {
        return LabourSite::query()
            ->forCurrentCompany()
            ->findOrFail($labourSite);
    }

    private function findContractor(int $contractor): Contractor
    {
        return Contractor::query()
            ->forCurrentCompany()
            ->findOrFail($contractor);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function contractorRules(bool $includeStatus = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'agreement_no' => ['nullable', 'string', 'max:255'],
            'contract_no' => ['nullable', 'string', 'max:255'],
            'work_order_no' => ['nullable', 'string', 'max:255'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'contract_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'progress_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'last_measurement_date' => ['nullable', 'date'],
            'last_measurement_summary' => ['nullable', 'string'],
            'last_ra_bill_no' => ['nullable', 'string', 'max:255'],
            'last_ra_bill_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'retention_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'recovery_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'tds_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'net_payable_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'renewal_due_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ];

        if ($includeStatus) {
            $rules['is_active'] = ['required', 'boolean'];
        }

        return $rules;
    }

    private function findLabour(int $labour): Labour
    {
        return Labour::query()
            ->forCurrentCompany()
            ->findOrFail($labour);
    }

    private function tenantTable(string $table): string
    {
        $connection = app(Tenant::class)->connectionName();

        return $connection ? $connection.'.'.$table : $table;
    }

    public function photo(LabourAttendance $labourAttendance): StreamedResponse|BinaryFileResponse
    {
        $photoPath = $this->publicPhotoPath($labourAttendance->photo_path);

        if ($photoPath) {
            return Storage::disk('public')->response($photoPath);
        }

        $publicStoragePath = $this->publicStoragePhotoPath($labourAttendance->photo_path);

        if ($publicStoragePath) {
            return response()->file($publicStoragePath);
        }

        $attendancePhotoPath = $this->attendancePhotoPath($labourAttendance);

        if ($attendancePhotoPath) {
            return response()->file($attendancePhotoPath);
        }

        abort(404);
    }

    private function publicPhotoPath(?string $photoPath): ?string
    {
        return $this->photoPathCandidates($photoPath)
            ->first(fn (string $path) => Storage::disk('public')->exists($path));
    }

    private function publicStoragePhotoPath(?string $photoPath): ?string
    {
        return $this->photoPathCandidates($photoPath)
            ->map(fn (string $path) => public_path('storage/' . $path))
            ->first(fn (string $path) => is_file($path));
    }

    private function attendancePhotoPath(LabourAttendance $labourAttendance): ?string
    {
        $attendanceId = (int) $labourAttendance->id;

        if ($attendanceId < 1) {
            return null;
        }

        $patterns = [
            storage_path('app/public/' . self::PHOTO_UPLOAD_DIR . '/*/' . $attendanceId . '/*'),
            storage_path('app/public/labour-attendance/*/' . $attendanceId . '/*'),
            public_path('storage/' . self::PHOTO_UPLOAD_DIR . '/*/' . $attendanceId . '/*'),
            public_path('storage/labour-attendance/*/' . $attendanceId . '/*'),
        ];

        foreach ($patterns as $pattern) {
            $file = collect(glob($pattern) ?: [])
                ->filter(fn (string $path) => is_file($path))
                ->sortDesc()
                ->first();

            if ($file) {
                return $file;
            }
        }

        return null;
    }

    private function photoPathCandidates(?string $photoPath): \Illuminate\Support\Collection
    {
        if (! $photoPath) {
            return collect();
        }

        $normalizedPath = str_replace('\\', '/', ltrim($photoPath, '/\\'));

        return collect([
            $photoPath,
            $normalizedPath,
            preg_replace('#^public/#', '', $normalizedPath),
            preg_replace('#^public/storage/#', '', $normalizedPath),
            preg_replace('#^storage/#', '', $normalizedPath),
            preg_replace('#^storage/app/public/#', '', $normalizedPath),
            preg_replace('#^labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^public/labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^public/storage/labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^storage/labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^storage/app/public/labour-attendance/#', self::PHOTO_UPLOAD_DIR . '/', $normalizedPath),
            preg_replace('#^' . preg_quote(self::PHOTO_UPLOAD_DIR, '#') . '/#', 'labour-attendance/', $normalizedPath),
        ])
            ->filter()
            ->map(fn (string $path) => str_replace('\\', '/', $path))
            ->reject(fn (string $path) => str_contains($path, '..'))
            ->unique()
            ->values();
    }

    private function isOutPending(LabourAttendance $attendance): bool
    {
        return in_array($attendance->status, ['present', 'half_day'], true)
            && filled($attendance->in_time)
            && blank($attendance->out_time);
    }
}
