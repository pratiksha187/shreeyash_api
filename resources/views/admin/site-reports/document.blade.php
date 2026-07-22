<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Site Report - {{ $site->name }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.35;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 22px;
        }

        h2 {
            margin: 22px 0 8px;
            padding: 8px 10px;
            background: #1f2937;
            color: #fff;
            font-size: 14px;
        }

        h3 {
            margin: 14px 0 6px;
            font-size: 12px;
        }

        p {
            margin: 0 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #d1d5db;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }

        .muted {
            color: #6b7280;
        }

        .header {
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #111827;
        }

        .summary-grid {
            width: 100%;
        }

        .summary-grid td {
            width: 16.66%;
            background: #f9fafb;
        }

        .status {
            font-weight: 700;
            text-transform: capitalize;
        }

        .photo {
            max-width: 90px;
            max-height: 70px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Site Complete Data Report</h1>
        <p><strong>Site:</strong> {{ $site->name }}</p>
        <p><strong>Address:</strong> {{ $site->address ?: '-' }}</p>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') }}</p>
        <p><strong>Generated:</strong> {{ now()->format('d M Y h:i A') }}</p>
    </div>

    <h2>Summary</h2>
    <table class="summary-grid">
        <tbody>
            <tr>
                <td><strong>Projects</strong><br>{{ $report['summary']['projects'] }}</td>
                <td><strong>Tasks</strong><br>{{ $report['summary']['tasks'] }}</td>
                <td><strong>Completed Tasks</strong><br>{{ $report['summary']['completed_tasks'] }}</td>
                <td><strong>Task Updates</strong><br>{{ $report['summary']['task_updates'] }}</td>
                <td><strong>Labour Entries</strong><br>{{ $report['summary']['labour_entries'] }}</td>
                <td><strong>Unique Labours</strong><br>{{ $report['summary']['unique_labours'] }}</td>
            </tr>
            <tr>
                <td><strong>Material Requests</strong><br>{{ $report['summary']['material_requests'] }}</td>
                <td><strong>Material Issues</strong><br>{{ $report['summary']['material_issues'] }}</td>
                <td><strong>DPR Reports</strong><br>{{ $report['summary']['dprs'] }}</td>
                <td><strong>Challans</strong><br>{{ $report['summary']['challans'] }}</td>
                <td><strong>Vehicle Entries</strong><br>{{ $report['summary']['vehicle_entries'] }}</td>
                <td><strong>Present Labour Entries</strong><br>{{ $report['summary']['present_labour_entries'] }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Projects</h2>
    <table>
        <thead>
            <tr>
                <th>Project</th>
                <th>Client</th>
                <th>Planning Manager</th>
                <th>Dates</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Tasks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['projects'] as $project)
                <tr>
                    <td>{{ $project->name }}<br><span class="muted">{{ $project->code ?: '-' }}</span></td>
                    <td>{{ $project->client_name ?: '-' }}</td>
                    <td>{{ $project->planningManager?->name ?? '-' }}</td>
                    <td>{{ $project->start_date?->format('d M Y') ?? '-' }}<br>Target: {{ $project->target_date?->format('d M Y') ?? '-' }}</td>
                    <td class="status">{{ str_replace('_', ' ', $project->status) }}</td>
                    <td>{{ $project->progress_percent }}%</td>
                    <td>{{ $project->tasks_count }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No projects found for this site.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Assigned Work / Tasks</h2>
    <table>
        <thead>
            <tr>
                <th>Task</th>
                <th>Engineer</th>
                <th>Supervisor</th>
                <th>Area</th>
                <th>Due</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['tasks'] as $task)
                <tr>
                    <td>{{ $task->title }}<br><span class="muted">{{ $task->description ?: '-' }}</span></td>
                    <td>{{ $task->engineer?->name ?? '-' }}</td>
                    <td>{{ $task->supervisor?->name ?? '-' }}</td>
                    <td>{{ $task->work_area ?: '-' }}</td>
                    <td>{{ $task->due_date?->format('d M Y') ?? '-' }}</td>
                    <td class="status">{{ str_replace('_', ' ', $task->status) }}</td>
                    <td>{{ $task->progress_percent }}%</td>
                    <td>Est: {{ $task->estimated_hours }}<br>Act: {{ $task->actual_hours }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No assigned tasks found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Task Updates / Remarks</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Task</th>
                <th>Employee</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Remark</th>
                <th>Photo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['task_updates'] as $update)
                <tr>
                    <td>{{ $update->created_at?->format('d M Y h:i A') }}</td>
                    <td>{{ $update->task?->title ?? '-' }}</td>
                    <td>{{ $update->user?->name ?? '-' }}</td>
                    <td class="status">{{ str_replace('_', ' ', (string) $update->status) }}</td>
                    <td>{{ $update->progress_percent }}%</td>
                    <td>{{ $update->remark ?: '-' }}</td>
                    <td>
                        @if ($update->photo_path)
                            {{ $update->photoUrl() }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No task updates found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Assigned Labours</h2>
    <table>
        <thead>
            <tr>
                <th>Labour</th>
                <th>Code</th>
                <th>Trade</th>
                <th>Mobile</th>
                <th>Contractor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['assigned_labours'] as $attendance)
                <tr>
                    <td>{{ $attendance->labour?->name ?? '-' }}</td>
                    <td>{{ $attendance->labour?->labour_code ?? '-' }}</td>
                    <td>{{ $attendance->labour?->trade ?? '-' }}</td>
                    <td>{{ $attendance->labour?->mobile ?? '-' }}</td>
                    <td>{{ $attendance->contractor?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No labours found for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Labour Attendance</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Labour</th>
                <th>Contractor</th>
                <th>Status</th>
                <th>Time</th>
                <th>Hours</th>
                <th>Engineer</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['labour_attendances'] as $attendance)
                <tr>
                    <td>{{ $attendance->attendance_date?->format('d M Y') }}</td>
                    <td>{{ $attendance->labour?->name ?? '-' }}</td>
                    <td>{{ $attendance->contractor?->name ?? '-' }}</td>
                    <td class="status">{{ str_replace('_', ' ', $attendance->status) }}</td>
                    <td>{{ $attendance->in_time ?: '-' }} - {{ $attendance->out_time ?: '-' }}</td>
                    <td>{{ $attendance->work_hours }}</td>
                    <td>{{ $attendance->engineer?->name ?? '-' }}</td>
                    <td>{{ $attendance->remarks ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No labour attendance found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Material Requests</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Material</th>
                <th>Requested</th>
                <th>Approved</th>
                <th>Issued</th>
                <th>Status</th>
                <th>Engineer</th>
                <th>Purpose</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['material_requests'] as $requestRow)
                <tr>
                    <td>{{ $requestRow->request_date?->format('d M Y') }}</td>
                    <td>{{ $requestRow->material?->name ?? $requestRow->material_name }} {{ $requestRow->unit ? '('.$requestRow->unit.')' : '' }}</td>
                    <td>{{ $requestRow->requested_quantity }}</td>
                    <td>{{ $requestRow->approved_quantity }}</td>
                    <td>{{ $requestRow->issued_quantity }}</td>
                    <td class="status">{{ str_replace('_', ' ', $requestRow->status) }}</td>
                    <td>{{ $requestRow->engineer?->name ?? '-' }}</td>
                    <td>{{ $requestRow->purpose ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No material requests found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Material Issues</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Material</th>
                <th>Quantity</th>
                <th>Issued By</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['material_issues'] as $issue)
                <tr>
                    <td>{{ $issue->issued_at?->format('d M Y h:i A') }}</td>
                    <td>{{ $issue->material?->name ?? '-' }} {{ $issue->material?->unit ? '('.$issue->material?->unit.')' : '' }}</td>
                    <td>{{ $issue->issued_quantity }}</td>
                    <td>{{ $issue->issuer?->name ?? '-' }}</td>
                    <td>{{ $issue->remarks ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No material issues found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>DPR Reports</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Engineer</th>
                <th>Summary</th>
                <th>Hours</th>
                <th>Files</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['dprs'] as $dpr)
                <tr>
                    <td>{{ $dpr->dpr_date?->format('d M Y') }}</td>
                    <td>{{ $dpr->user?->name ?? '-' }}</td>
                    <td>{{ $dpr->work_summary }}</td>
                    <td>
                        @foreach ($dpr->hours->sortBy('hour_number') as $hour)
                            {{ $hour->work_time }} - {{ $hour->remark ?: '-' }}<br>
                        @endforeach
                    </td>
                    <td>{{ $dpr->hours->sum(fn ($hour) => $hour->photos->count()) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No DPR reports found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Challans</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Challan No</th>
                <th>Party</th>
                <th>Material / M/c</th>
                <th>Vehicle</th>
                <th>Measurement</th>
                <th>Submitted By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['challans'] as $challan)
                <tr>
                    <td>{{ $challan->challan_date?->format('d M Y') }}</td>
                    <td>{{ $challan->challan_no }}</td>
                    <td>{{ $challan->party_name }}</td>
                    <td>{{ $challan->material_machine }}</td>
                    <td>{{ $challan->vehicle_no ?: '-' }}</td>
                    <td>{{ $challan->measurement ?: '-' }}</td>
                    <td>{{ $challan->user?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No challans found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Vehicle Entries</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Vehicle</th>
                <th>Driver</th>
                <th>Challan</th>
                <th>In</th>
                <th>Out</th>
                <th>Diesel</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['vehicle_logs'] as $log)
                <tr>
                    <td>{{ $log->entry_date?->format('d M Y') }}</td>
                    <td>{{ $log->vehicle?->vehicle_number ?? $log->vehicle_number }}</td>
                    <td>{{ $log->driver_name ?: '-' }}</td>
                    <td>{{ $log->challan_no ?: '-' }}</td>
                    <td>{{ $log->in_at?->format('h:i A') ?? '-' }}</td>
                    <td>{{ $log->out_at?->format('h:i A') ?? '-' }}</td>
                    <td>{{ $log->diesel_added }}</td>
                    <td>{{ $log->remarks ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No vehicle entries found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
