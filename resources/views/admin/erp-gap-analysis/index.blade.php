@extends('admin.layouts.app')

@section('title', 'ERP Gap Analysis | Admin Panel')
@section('headerTitle', 'ERP Gap Analysis')
@section('headerSubtitle', '')
@section('bodyClass', 'gap-page')

@section('content')
    <style>
        body.gap-page .main {
            max-width: none;
        }

        .gap-summary {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .gap-table th,
        .gap-table td {
            vertical-align: top;
            white-space: normal;
        }

        .gap-table td {
            line-height: 1.45;
        }

        .gap-module {
            min-width: 180px;
        }

        .gap-description {
            min-width: 260px;
        }

        .gap-pill {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .gap-pill.implemented {
            background: #dcfce7;
            color: #166534;
        }

        .gap-pill.partial {
            background: #fef3c7;
            color: #92400e;
        }

        .gap-pill.missing {
            background: #fee2e2;
            color: #991b1b;
        }

        .priority-pill {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #075985;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .workflow-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 18px;
        }

        .workflow-step {
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #f8fafc;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
        }

        .phase-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-top: 18px;
        }

        .phase-card {
            padding: 16px;
        }

        .phase-card span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .phase-card strong {
            display: block;
            margin-top: 8px;
            font-size: 16px;
        }

        .phase-card p {
            line-height: 1.45;
        }

        @media (max-width: 900px) {
            .gap-summary,
            .phase-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .gap-summary,
            .phase-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="gap-page">
        <div class="page-header">
            <div>
                <h1>ERP Gap Analysis</h1>
                <p>Mapped from the NWAY ERP Complete Analysis Report to the modules currently present in this Laravel project.</p>
            </div>
        </div>

        <section class="stats-grid gap-summary">
            <div class="card stat-card">
                <span>Total Modules</span>
                <strong>{{ $summary['total'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>Implemented</span>
                <strong>{{ $summary['implemented'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>Partial</span>
                <strong>{{ $summary['partial'] }}</strong>
            </div>
            <div class="card stat-card">
                <span>Missing</span>
                <strong>{{ $summary['missing'] }}</strong>
            </div>
        </section>

        <div class="card table-wrap">
            <table class="gap-table">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Document Scope</th>
                        <th>Current Project</th>
                        <th>Gap To Add</th>
                        <th>Status</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modules as $module)
                        <tr>
                            <td class="gap-module"><strong>{{ $module['name'] }}</strong></td>
                            <td class="gap-description">{{ $module['document_scope'] }}</td>
                            <td class="gap-description">{{ $module['current_project'] }}</td>
                            <td class="gap-description">{{ $module['gap'] }}</td>
                            <td>
                                <span class="gap-pill {{ $module['status'] }}">
                                    {{ str_replace('_', ' ', $module['status']) }}
                                </span>
                            </td>
                            <td><span class="priority-pill">{{ $module['priority'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="page-header section-spacer">
            <div>
                <h1>Recommended Build Phases</h1>
                <p>Use this order to add the missing ERP depth without activating everything at once.</p>
            </div>
        </div>

        <section class="phase-grid">
            @foreach ($phases as $phase)
                <div class="card phase-card">
                    <span>{{ $phase['phase'] }}</span>
                    <strong>{{ $phase['modules'] }}</strong>
                    <p>{{ $phase['goal'] }}</p>
                </div>
            @endforeach
        </section>

        <div class="page-header section-spacer">
            <div>
                <h1>End-to-End ERP Workflow</h1>
                <p>This is the transaction chain the document says the system should eventually support.</p>
            </div>
        </div>

        <section class="card workflow-strip">
            @foreach ($demoWorkflow as $step)
                <span class="workflow-step">{{ $step }}</span>
            @endforeach
        </section>
    </div>
@endsection
