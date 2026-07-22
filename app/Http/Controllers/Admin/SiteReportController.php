<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challan;
use App\Models\DailyProgressReport;
use App\Models\LabourAttendance;
use App\Models\LabourSite;
use App\Models\MaterialIssue;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUpdate;
use App\Models\VehicleLog;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SiteReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCompanyAdmin();

        $filters = $this->filters($request);
        $site = isset($filters['labour_site_id']) ? $this->findSite((int) $filters['labour_site_id']) : null;

        return view('admin.site-reports.index', [
            'sites' => $this->sites(),
            'site' => $site,
            'filters' => $filters,
            'report' => $site ? $this->buildReport($site, $filters) : null,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $this->ensureCompanyAdmin();

        $filters = $this->filters($request);
        $site = $this->findSite((int) $filters['labour_site_id']);
        $report = $this->buildReport($site, $filters);
        $fileName = $this->fileName($site, $filters, 'pdf');

        return app('dompdf.wrapper')->loadView('admin.site-reports.document', [
            'site' => $site,
            'filters' => $filters,
            'report' => $report,
            'format' => 'pdf',
        ])->setPaper('a4', 'landscape')->download($fileName);
    }

    public function word(Request $request): Response
    {
        $this->ensureCompanyAdmin();

        $filters = $this->filters($request);
        $site = $this->findSite((int) $filters['labour_site_id']);
        $report = $this->buildReport($site, $filters);

        return response()
            ->view('admin.site-reports.document', [
                'site' => $site,
                'filters' => $filters,
                'report' => $report,
                'format' => 'word',
            ])
            ->header('Content-Type', 'application/msword; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$this->fileName($site, $filters, 'doc').'"');
    }

    private function buildReport(LabourSite $site, array $filters): array
    {
        $fromDate = Carbon::parse($filters['from_date'])->toDateString();
        $toDate = Carbon::parse($filters['to_date'])->toDateString();

        $projects = $this->projects($site);
        $projectIds = $projects->pluck('id');

        $tasks = $this->hasTable('project_tasks') && $projectIds->isNotEmpty()
            ? ProjectTask::query()
                ->forCurrentCompany()
                ->with(['project:id,name,site_location', 'engineer:id,name,mobile,designation', 'supervisor:id,name,mobile,designation'])
                ->whereIn('project_id', $projectIds)
                ->where(function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('start_date', [$fromDate, $toDate])
                        ->orWhereBetween('due_date', [$fromDate, $toDate])
                        ->orWhereNull('start_date')
                        ->orWhereNull('due_date');
                })
                ->orderBy('due_date')
                ->get()
            : collect();

        $taskUpdates = $this->hasTable('project_task_updates') && $tasks->isNotEmpty()
            ? ProjectTaskUpdate::query()
                ->forCurrentCompany()
                ->with(['task:id,title,project_id', 'user:id,name,mobile,designation'])
                ->whereIn('project_task_id', $tasks->pluck('id'))
                ->whereBetween('created_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59'])
                ->latest()
                ->get()
            : collect();

        $labourAttendances = LabourAttendance::query()
            ->forCurrentCompany()
            ->with(['engineer:id,name,mobile,designation', 'contractor:id,name', 'labour:id,name,mobile,labour_code,trade'])
            ->where('labour_site_id', $site->id)
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->orderByDesc('attendance_date')
            ->get();

        $materialRequests = $this->hasTable('material_requests')
            ? MaterialRequest::query()
                ->forCurrentCompany()
                ->with(['engineer:id,name,mobile,designation', 'material:id,name,unit'])
                ->where('labour_site_id', $site->id)
                ->whereBetween('request_date', [$fromDate, $toDate])
                ->latest('request_date')
                ->get()
            : collect();

        $materialIssues = $this->hasTable('material_issues')
            ? MaterialIssue::query()
                ->forCurrentCompany()
                ->with(['material:id,name,unit', 'issuer:id,name'])
                ->where('labour_site_id', $site->id)
                ->whereBetween('issued_at', [$fromDate.' 00:00:00', $toDate.' 23:59:59'])
                ->latest('issued_at')
                ->get()
            : collect();

        $dprs = DailyProgressReport::query()
            ->forCurrentCompany()
            ->with(['user:id,name,mobile,designation', 'hours.photos'])
            ->whereBetween('dpr_date', [$fromDate, $toDate])
            ->where(function ($query) use ($site) {
                $query->where('site_project', $site->name)
                    ->orWhere('site_project', 'like', '%'.$site->name.'%');
            })
            ->orderByDesc('dpr_date')
            ->get();

        $challans = Challan::query()
            ->forCurrentCompany()
            ->with('user:id,name,mobile,designation')
            ->whereBetween('challan_date', [$fromDate, $toDate])
            ->where(function ($query) use ($site) {
                $query->where('location', $site->name)
                    ->orWhere('location', 'like', '%'.$site->name.'%');
            })
            ->latest('challan_date')
            ->get();

        $vehicleLogs = VehicleLog::query()
            ->forCurrentCompany()
            ->with('vehicle:id,vehicle_number,vehicle_type')
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->where(function ($query) use ($site) {
                $query->where('site_name', $site->name)
                    ->orWhere('site_name', 'like', '%'.$site->name.'%');
            })
            ->latest('entry_date')
            ->get();

        return [
            'summary' => [
                'projects' => $projects->count(),
                'tasks' => $tasks->count(),
                'completed_tasks' => $tasks->where('status', 'completed')->count(),
                'task_updates' => $taskUpdates->count(),
                'labour_entries' => $labourAttendances->count(),
                'present_labour_entries' => $labourAttendances->where('status', 'present')->count(),
                'unique_labours' => $labourAttendances->pluck('labour_id')->filter()->unique()->count(),
                'material_requests' => $materialRequests->count(),
                'material_issues' => $materialIssues->count(),
                'dprs' => $dprs->count(),
                'challans' => $challans->count(),
                'vehicle_entries' => $vehicleLogs->count(),
            ],
            'projects' => $projects,
            'tasks' => $tasks,
            'task_updates' => $taskUpdates,
            'labour_attendances' => $labourAttendances,
            'assigned_labours' => $labourAttendances
                ->filter(fn ($attendance) => $attendance->labour)
                ->unique('labour_id')
                ->values(),
            'material_requests' => $materialRequests,
            'material_issues' => $materialIssues,
            'dprs' => $dprs,
            'challans' => $challans,
            'vehicle_logs' => $vehicleLogs,
        ];
    }

    private function projects(LabourSite $site)
    {
        if (! $this->hasTable('projects')) {
            return collect();
        }

        return Project::query()
            ->forCurrentCompany()
            ->with(['planningManager:id,name,mobile,designation'])
            ->withCount(['tasks'])
            ->where(function ($query) use ($site) {
                $query->where('site_location', $site->name)
                    ->orWhere('site_location', 'like', '%'.$site->name.'%');
            })
            ->latest()
            ->get();
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'labour_site_id' => ['nullable', 'integer'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        return [
            'labour_site_id' => $data['labour_site_id'] ?? null,
            'from_date' => $data['from_date'] ?? now()->startOfMonth()->toDateString(),
            'to_date' => $data['to_date'] ?? now()->toDateString(),
        ];
    }

    private function findSite(int $siteId): LabourSite
    {
        return LabourSite::query()
            ->forCurrentCompany()
            ->findOrFail($siteId);
    }

    private function sites()
    {
        return LabourSite::query()
            ->forCurrentCompany()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);
    }

    private function fileName(LabourSite $site, array $filters, string $extension): string
    {
        $safeSite = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($site->name)) ?: 'site';

        return 'site-report-'.$safeSite.'-'.$filters['from_date'].'-to-'.$filters['to_date'].'.'.$extension;
    }

    private function hasTable(string $table): bool
    {
        return DB::connection(app(Tenant::class)->connectionName())
            ->getSchemaBuilder()
            ->hasTable($table);
    }

    private function ensureCompanyAdmin(): void
    {
        if (session()->has('admin_company_id') && app(Tenant::class)->hasCompany()) {
            return;
        }

        abort(403, 'Please login with an employer/company admin account to view site reports.');
    }
}
