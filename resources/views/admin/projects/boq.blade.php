@extends('admin.layouts.app')

@section('title', 'BOQ - '.$project->name.' | Admin Panel')
@section('headerTitle', 'Project BOQ')
@section('headerSubtitle', 'Manual BOQ entry and Excel import for project quantities')
@section('bodyClass', 'project-boq-page')

@section('content')
    <style>
        body.project-boq-page .main {
            width: 100%;
            max-width: 1460px;
            overflow: hidden;
        }

        body.project-boq-page {
            overflow-x: hidden;
        }

        body.project-boq-page .form-card,
        body.project-boq-page .table-wrap,
        body.project-boq-page .stats-grid,
        body.project-boq-page .page-header {
            min-width: 0;
            max-width: 100%;
        }

        body.project-boq-page .table-wrap {
            max-width: 100%;
            overflow-x: auto;
        }

        body.project-boq-page .page-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: start;
            gap: 18px;
        }

        body.project-boq-page .page-header > div:first-child {
            min-width: 0;
        }

        .boq-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: 10px;
            max-width: 100%;
        }

        .boq-toolbar .btn {
            max-width: 100%;
            white-space: nowrap;
        }

        .boq-summary {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .boq-form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px 20px;
        }

        .boq-form-grid .wide {
            grid-column: span 2;
        }

        .boq-entry-card {
            padding: 18px 18px 20px;
        }

        .boq-entry-card .section-title {
            margin-bottom: 14px;
            font-size: 22px;
        }

        .boq-entry-wrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
        }

        .boq-entry-table {
            min-width: 1080px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        .boq-entry-table th,
        .boq-entry-table td {
            padding: 7px;
            border-right: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            background: #fff;
            vertical-align: middle;
        }

        .boq-entry-table th {
            background: #f8fbff;
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .boq-entry-table th:last-child,
        .boq-entry-table td:last-child {
            border-right: 0;
        }

        .boq-entry-table tr:last-child td {
            border-bottom: 0;
        }

        .boq-entry-table input,
        .boq-entry-table select {
            width: 100%;
            min-height: 34px;
            padding: 6px 8px;
            border-radius: 4px;
            font-size: 13px;
        }

        .boq-entry-table th:nth-child(1) { width: 110px; }
        .boq-entry-table th:nth-child(2) { width: 110px; }
        .boq-entry-table th:nth-child(3) { width: 190px; }
        .boq-entry-table th:nth-child(4) { width: 330px; }
        .boq-entry-table th:nth-child(5) { width: 90px; }
        .boq-entry-table th:nth-child(6),
        .boq-entry-table th:nth-child(7),
        .boq-entry-table th:nth-child(8) { width: 110px; }
        .boq-entry-table th:nth-child(9) { width: 80px; }
        .boq-entry-table th:nth-child(10) { width: 70px; }

        .boq-entry-actions {
            display: flex;
            justify-content: flex-start;
            margin-top: 12px;
        }

        .boq-import-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 14px;
            align-items: start;
        }

        .boq-import-grid input[type="file"] {
            width: 100%;
        }

        .boq-import-grid .btn {
            justify-self: start;
        }

        .checkbox-inline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            font-weight: 800;
            color: var(--brand-blue-dark);
            justify-self: start;
            white-space: normal;
        }

        .checkbox-inline input {
            width: 18px;
            height: 18px;
        }

        .boq-table {
            min-width: 980px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        .boq-table th,
        .boq-table td {
            padding: 8px 9px;
            border-right: 1px solid var(--line);
            vertical-align: middle;
            white-space: normal;
        }

        .boq-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8fbff;
        }

        .boq-table th:last-child,
        .boq-table td:last-child {
            border-right: 0;
        }

        .boq-table th:nth-child(1) { width: 120px; }
        .boq-table th:nth-child(2) { width: 430px; }
        .boq-table th:nth-child(3) { width: 80px; }
        .boq-table th:nth-child(4),
        .boq-table th:nth-child(5),
        .boq-table th:nth-child(6) { width: 130px; }
        .boq-table th:nth-child(7) { width: 110px; }

        .boq-row-group td {
            background: #fff1dc;
            font-weight: 900;
        }

        .boq-row-group td:first-child {
            border-left: 4px solid var(--primary);
        }

        .boq-row-child td {
            background: #ffffff;
        }

        .boq-row-ungrouped td {
            background: #f8fbff;
        }

        .boq-title-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .boq-tree-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .boq-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            flex: 0 0 auto;
            border: 1px solid #f6b26b;
            border-radius: 4px;
            background: #ffffff;
            color: var(--primary-dark);
            cursor: pointer;
            font-size: 16px;
            font-weight: 900;
            line-height: 1;
        }

        .boq-toggle:hover {
            background: #fff7ed;
        }

        .boq-toggle-placeholder {
            display: inline-block;
            width: 22px;
            height: 22px;
            flex: 0 0 auto;
        }

        .boq-no-text {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .boq-indent {
            display: inline-block;
            width: 34px;
            flex: 0 0 auto;
            color: var(--primary);
            font-weight: 900;
        }

        .boq-group-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 54px;
            min-height: 24px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #e5f0fb;
            color: var(--brand-blue-dark);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .boq-empty-child {
            color: var(--muted);
            font-weight: 700;
        }

        .boq-delete-form {
            margin: 0;
        }

        .boq-row-actions {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .boq-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 4px;
            background: #e5f0fb;
            color: var(--brand-blue-dark);
            cursor: pointer;
            font-size: 18px;
            font-weight: 900;
            text-decoration: none;
        }

        .boq-icon-btn:hover {
            background: var(--primary);
            color: #fff;
        }

        .boq-icon-btn.danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .boq-icon-btn.danger:hover {
            background: #dc2626;
            color: #fff;
        }

        .boq-collapsed-child {
            display: none;
        }

        @media (max-width: 1000px) {
            body.project-boq-page .page-header,
            .boq-summary,
            .boq-import-grid {
                grid-template-columns: 1fr;
            }

            .boq-toolbar {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>{{ $project->name }} BOQ</h1>
            <p>{{ $project->code ?: 'Project' }}{{ $project->site_location ? ' | '.$project->site_location : '' }}</p>
        </div>
        <div class="boq-toolbar">
            <a class="btn secondary" href="{{ route('admin.projects.show', $project) }}">Back to Project</a>
            <a class="btn secondary" href="{{ route('admin.projects.boq.template', $project) }}">Download Demo Excel</a>
            <a class="btn" href="{{ route('admin.projects.boq.export', $project) }}">Download BOQ Excel</a>
            <a class="btn" href="{{ route('admin.projects.show', $project) }}#project-planning">Next: Planning</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <section class="stats-grid boq-summary">
        <div class="card stat-card">
            <span>Total BOQ Rows</span>
            <strong>{{ $summary['total_items'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Total Qty</span>
            <strong>{{ number_format($summary['scope_qty'], 3) }}</strong>
        </div>
        <div class="card stat-card">
            <span>BOQ Amount</span>
            <strong>{{ number_format($summary['boq_amount'], 2) }}</strong>
        </div>
    </section>

    <form class="card form-card" method="POST" action="{{ route('admin.projects.boq.import', $project) }}" enctype="multipart/form-data">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Excel Upload</h2>
            <div class="boq-import-grid">
                <div class="field">
                    <label for="boq_file">BOQ Excel / CSV File</label>
                    <input id="boq_file" name="boq_file" type="file" accept=".xlsx,.csv,.txt" required>
                </div>
                <label class="checkbox-inline">
                    <input name="replace_existing" type="checkbox" value="1">
                    Replace existing BOQ
                </label>
                <button class="btn" type="submit">Upload BOQ</button>
            </div>
        </section>
    </form>

    <form id="boq-manual-form" class="card form-card boq-entry-card section-spacer" method="POST" action="{{ route('admin.projects.boq.store', $project) }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">BOQ Sheet Entry</h2>
            <div class="boq-entry-wrap">
                <table class="boq-entry-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>S.N</th>
                            <th>Parent</th>
                            <th>Description</th>
                            <th>UON</th>
                            <th>Total</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>Sort</th>
                            <th>Add</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select id="item_type" name="item_type" required>
                                    <option value="group" @selected(old('item_type') === 'group')>Heading</option>
                                    <option value="item" @selected(old('item_type', 'item') === 'item')>Item</option>
                                </select>
                            </td>
                            <td><input id="boq_no" name="boq_no" type="text" value="{{ old('boq_no') }}"></td>
                            <td>
                                <select id="parent_boq_no" name="parent_boq_no">
                                    <option value="">Main row</option>
                                    @foreach ($groupItems as $group)
                                        <option value="{{ $group->boq_no }}" @selected(old('parent_boq_no') === $group->boq_no)>
                                            {{ $group->boq_no }} - {{ $group->task_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input id="task_name" name="task_name" type="text" value="{{ old('task_name') }}" placeholder="BOQ description / heading" required></td>
                            <td><input id="unit" name="unit" type="text" value="{{ old('unit') }}" placeholder="CUM"></td>
                            <td><input id="scope_qty" name="scope_qty" type="number" min="0" step="0.001" value="{{ old('scope_qty', 0) }}"></td>
                            <td><input id="rate" name="rate" type="number" min="0" step="0.01" value="{{ old('rate', 0) }}"></td>
                            <td><input id="boq_amount_preview" type="text" value="0.00" readonly></td>
                            <td><input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order') }}"></td>
                            <td><button class="boq-icon-btn" type="submit" title="Add BOQ row">+</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <input id="group_name" name="group_name" type="hidden" value="{{ old('group_name') }}">
            <input name="tender_qty" type="hidden" value="0">
            <input name="subcontractor_done_qty" type="hidden" value="0">
            <input name="self_done_qty" type="hidden" value="0">
            <input name="done_qty" type="hidden" value="0">
            <input name="billed_amount" type="hidden" value="0">
            <input name="dpr_unbilled_amount" type="hidden" value="0">
        </section>
    </form>

    <div class="page-header section-spacer">
        <div>
            <h1>BOQ Hierarchy</h1>
            <p>Main BOQ groups with multiple child activity rows below each group.</p>
        </div>
    </div>

    <div class="card table-wrap">
        <table class="boq-table">
            <thead>
                <tr>
                    <th>S.N</th>
                    <th>Description</th>
                    <th>UON</th>
                    <th>Total</th>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groupItems as $group)
                    @php($groupKey = 'boq-group-'.$group->id)
                    <tr class="boq-row-group" data-group-row="{{ $groupKey }}">
                        @include('admin.projects.partials.boq-row', [
                            'item' => $group,
                            'project' => $project,
                            'isChild' => false,
                            'aggregate' => $groupTotals->get($group->boq_no),
                            'groupKey' => $groupKey,
                            'childCount' => $childItems->get($group->boq_no, collect())->count(),
                        ])
                    </tr>

                    @forelse ($childItems->get($group->boq_no, collect()) as $item)
                        <tr class="boq-row-child" data-parent-group="{{ $groupKey }}">
                            @include('admin.projects.partials.boq-row', [
                                'item' => $item,
                                'project' => $project,
                                'isChild' => true,
                                'aggregate' => null,
                                'groupKey' => null,
                                'childCount' => 0,
                            ])
                        </tr>
                    @empty
                        <tr data-parent-group="{{ $groupKey }}">
                            <td></td>
                            <td colspan="6" class="boq-empty-child"><span class="boq-indent">-&gt;</span>No child rows added under this heading yet.</td>
                        </tr>
                    @endforelse
                @empty
                    @forelse ($childItems->get('__ungrouped', collect()) as $item)
                        <tr class="boq-row-ungrouped">
                            @include('admin.projects.partials.boq-row', [
                                'item' => $item,
                                'project' => $project,
                                'isChild' => false,
                                'aggregate' => null,
                                'groupKey' => null,
                                'childCount' => 0,
                            ])
                        </tr>
                    @empty
                        <tr>
                            <td class="empty" colspan="7">No BOQ rows added yet. First add a Heading row, then add Item rows under that parent.</td>
                        </tr>
                    @endforelse
                @endforelse

                @if ($groupItems->isNotEmpty() && $childItems->has('__ungrouped'))
                    <tr class="boq-row-group">
                        <td>-</td>
                        <td><strong>Ungrouped Items</strong><div class="table-subtext">Rows without parent heading</div></td>
                        <td colspan="5"></td>
                    </tr>
                    @foreach ($childItems->get('__ungrouped') as $item)
                        <tr class="boq-row-ungrouped">
                            @include('admin.projects.partials.boq-row', [
                                'item' => $item,
                                'project' => $project,
                                'isChild' => true,
                                'aggregate' => null,
                                'groupKey' => null,
                                'childCount' => 0,
                            ])
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <script>
        document.querySelectorAll('[data-boq-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var group = button.getAttribute('data-boq-toggle');
                var collapsed = button.getAttribute('aria-expanded') === 'true';

                document.querySelectorAll('[data-parent-group="' + group + '"]').forEach(function (row) {
                    row.classList.toggle('boq-collapsed-child', collapsed);
                });

                button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                button.textContent = collapsed ? '+' : '-';
            });
        });

        document.querySelectorAll('[data-add-child]').forEach(function (button) {
            button.addEventListener('click', function () {
                var parent = button.getAttribute('data-parent-boq') || '';
                var group = button.getAttribute('data-parent-group-name') || '';
                var form = document.getElementById('boq-manual-form');

                document.getElementById('item_type').value = 'item';
                document.getElementById('parent_boq_no').value = parent;
                document.getElementById('group_name').value = group;
                document.getElementById('task_name').focus();

                if (form) {
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        function updateBoqAmountPreview() {
            var total = parseFloat(document.getElementById('scope_qty').value || '0');
            var rate = parseFloat(document.getElementById('rate').value || '0');
            var preview = document.getElementById('boq_amount_preview');

            if (preview) {
                preview.value = (total * rate).toFixed(2);
            }
        }

        ['scope_qty', 'rate'].forEach(function (id) {
            var input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', updateBoqAmountPreview);
            }
        });

        updateBoqAmountPreview();
    </script>
@endsection
