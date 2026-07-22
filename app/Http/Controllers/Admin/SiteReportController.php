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

        return response($this->buildPdf($site, $filters, $report))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
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

    private function buildPdf(LabourSite $site, array $filters, array $report): string
    {
        $lines = $this->reportLines($site, $filters, $report);
        $pages = array_chunk($lines, 42);
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
        ];

        $pageObjectNumbers = [];
        $fontObjectNumber = 3 + (count($pages) * 2);

        foreach ($pages as $index => $pageLines) {
            $pageObjectNumber = 3 + ($index * 2);
            $contentObjectNumber = $pageObjectNumber + 1;
            $pageObjectNumbers[] = $pageObjectNumber.' 0 R';

            $objects[] = "{$pageObjectNumber} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 {$fontObjectNumber} 0 R >> >> /Contents {$contentObjectNumber} 0 R >>\nendobj\n";

            $content = "BT\n/F1 9 Tf\n0 0 0 rg\n";
            $y = 560;
            foreach ($pageLines as $line) {
                $content .= "36 {$y} Td\n(".$this->escapePdfText($line).") Tj\n-36 0 Td\n";
                $y -= 12;
            }
            $content .= "ET\n";

            $objects[] = "{$contentObjectNumber} 0 obj\n<< /Length ".strlen($content)." >>\nstream\n{$content}endstream\nendobj\n";
        }

        $kids = implode(' ', $pageObjectNumbers);
        array_splice($objects, 1, 0, "2 0 obj\n<< /Type /Pages /Kids [{$kids}] /Count ".count($pages)." >>\nendobj\n");
        $objects[] = "{$fontObjectNumber} 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function reportLines(LabourSite $site, array $filters, array $report): array
    {
        $lines = [
            'SITE COMPLETE DATA REPORT',
            'Site: '.$site->name,
            'Address: '.($site->address ?: '-'),
            'Period: '.Carbon::parse($filters['from_date'])->format('d M Y').' to '.Carbon::parse($filters['to_date'])->format('d M Y'),
            'Generated: '.now()->format('d M Y h:i A'),
            '',
            'SUMMARY',
        ];

        foreach ($report['summary'] as $label => $value) {
            $lines[] = $this->label($label).': '.$value;
        }

        $this->appendSection($lines, 'PROJECTS', $report['projects'], function ($project) {
            return [
                $project->name.' | Client: '.($project->client_name ?: '-').' | Manager: '.($project->planningManager?->name ?? '-'),
                'Status: '.$project->status.' | Progress: '.$project->progress_percent.'% | Target: '.($project->target_date?->format('d M Y') ?? '-'),
            ];
        });

        $this->appendSection($lines, 'ASSIGNED TASKS', $report['tasks'], function ($task) {
            return [
                $task->title.' | Engineer: '.($task->engineer?->name ?? '-').' | Supervisor: '.($task->supervisor?->name ?? '-'),
                'Area: '.($task->work_area ?: '-').' | Status: '.$task->status.' | Progress: '.$task->progress_percent.'% | Due: '.($task->due_date?->format('d M Y') ?? '-'),
            ];
        });

        $this->appendSection($lines, 'TASK UPDATES / REMARKS', $report['task_updates'], function ($update) {
            return [
                ($update->created_at?->format('d M Y h:i A') ?? '-').' | Task: '.($update->task?->title ?? '-').' | By: '.($update->user?->name ?? '-'),
                'Status: '.$update->status.' | Progress: '.$update->progress_percent.'% | Remark: '.($update->remark ?: '-').' | Photo: '.($update->photo_path ?: '-'),
            ];
        });

        $this->appendSection($lines, 'ASSIGNED LABOURS', $report['assigned_labours'], function ($attendance) {
            return [
                ($attendance->labour?->name ?? '-').' | Code: '.($attendance->labour?->labour_code ?? '-').' | Trade: '.($attendance->labour?->trade ?? '-').' | Contractor: '.($attendance->contractor?->name ?? '-'),
            ];
        });

        $this->appendSection($lines, 'LABOUR ATTENDANCE', $report['labour_attendances'], function ($attendance) {
            return [
                ($attendance->attendance_date?->format('d M Y') ?? '-').' | '.($attendance->labour?->name ?? '-').' | '.$attendance->status.' | Hours: '.$attendance->work_hours,
                'Engineer: '.($attendance->engineer?->name ?? '-').' | Remarks: '.($attendance->remarks ?: '-'),
            ];
        });

        $this->appendSection($lines, 'MATERIAL REQUESTS', $report['material_requests'], function ($requestRow) {
            return [
                ($requestRow->request_date?->format('d M Y') ?? '-').' | '.($requestRow->material?->name ?? $requestRow->material_name).' | Qty: '.$requestRow->requested_quantity.' | Status: '.$requestRow->status,
                'Engineer: '.($requestRow->engineer?->name ?? '-').' | Purpose: '.($requestRow->purpose ?: '-'),
            ];
        });

        $this->appendSection($lines, 'MATERIAL ISSUES', $report['material_issues'], function ($issue) {
            return [
                ($issue->issued_at?->format('d M Y h:i A') ?? '-').' | '.($issue->material?->name ?? '-').' | Qty: '.$issue->issued_quantity.' | By: '.($issue->issuer?->name ?? '-'),
            ];
        });

        $this->appendSection($lines, 'DPR REPORTS', $report['dprs'], function ($dpr) {
            return [
                ($dpr->dpr_date?->format('d M Y') ?? '-').' | Engineer: '.($dpr->user?->name ?? '-').' | Files: '.$dpr->hours->sum(fn ($hour) => $hour->photos->count()),
                'Summary: '.$dpr->work_summary,
            ];
        });

        $this->appendSection($lines, 'CHALLANS', $report['challans'], function ($challan) {
            return [
                ($challan->challan_date?->format('d M Y') ?? '-').' | No: '.$challan->challan_no.' | Party: '.$challan->party_name.' | Material/M/c: '.$challan->material_machine,
                'Vehicle: '.($challan->vehicle_no ?: '-').' | Measurement: '.($challan->measurement ?: '-').' | By: '.($challan->user?->name ?? '-'),
            ];
        });

        $this->appendSection($lines, 'VEHICLE ENTRIES', $report['vehicle_logs'], function ($log) {
            return [
                ($log->entry_date?->format('d M Y') ?? '-').' | Vehicle: '.($log->vehicle?->vehicle_number ?? $log->vehicle_number).' | Driver: '.($log->driver_name ?: '-'),
                'In: '.($log->in_at?->format('h:i A') ?? '-').' | Out: '.($log->out_at?->format('h:i A') ?? '-').' | Diesel: '.$log->diesel_added.' | Remarks: '.($log->remarks ?: '-'),
            ];
        });

        return collect($lines)
            ->flatMap(fn (string $line) => $this->wrapLine($line, 138))
            ->values()
            ->all();
    }

    private function appendSection(array &$lines, string $title, $records, callable $formatter): void
    {
        $lines[] = '';
        $lines[] = $title;

        if ($records->isEmpty()) {
            $lines[] = 'No data found.';
            return;
        }

        foreach ($records as $index => $record) {
            $lines[] = ($index + 1).'.';
            foreach ($formatter($record) as $line) {
                $lines[] = '   '.$line;
            }
        }
    }

    private function label(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }

    private function wrapLine(string $line, int $limit): array
    {
        $line = $this->normalizePdfText($line);

        return explode("\n", wordwrap($line, $limit, "\n", true));
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->normalizePdfText($text));
    }

    private function normalizePdfText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?: '-';

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

            if ($converted !== false) {
                return $converted;
            }
        }

        return $text;
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
