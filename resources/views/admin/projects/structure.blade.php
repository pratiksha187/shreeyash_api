@extends('admin.layouts.app')

@section('title', 'Project Structure - '.$project->name.' | Admin Panel')
@section('headerTitle', 'Project Structure')
@section('headerSubtitle', 'Build phase, layer, task, and sub-task hierarchy')

@section('content')
    <style>
        .structure-sheet {
            overflow-x: auto;
        }

        .structure-guide {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .structure-guide-step {
            position: relative;
            min-height: 112px;
            padding: 16px;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            background: #ffffff;
        }

        .structure-guide-step::after {
            content: "";
            position: absolute;
            right: -13px;
            top: 50%;
            width: 12px;
            height: 2px;
            background: var(--primary);
        }

        .structure-guide-step:last-child::after {
            display: none;
        }

        .structure-guide-step span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            margin-bottom: 8px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-weight: 900;
        }

        .structure-guide-step strong {
            display: block;
            color: var(--ink);
            font-size: 15px;
        }

        .structure-guide-step small {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-weight: 700;
            line-height: 1.35;
        }

        .structure-entry-table,
        .structure-table {
            min-width: 980px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        .structure-entry-table th,
        .structure-entry-table td,
        .structure-table th,
        .structure-table td {
            padding: 9px 10px;
            border-right: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }

        .structure-entry-table th,
        .structure-table th {
            background: #f8fbff;
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .structure-entry-table input,
        .structure-entry-table select {
            width: 100%;
            min-height: 36px;
            padding: 7px 9px;
            border-radius: 4px;
            font-size: 13px;
        }

        .structure-entry-table th:nth-child(1) { width: 130px; }
        .structure-entry-table th:nth-child(2) { width: 260px; }
        .structure-entry-table th:nth-child(3) { width: 300px; }
        .structure-entry-table th:nth-child(4) { width: 180px; }
        .structure-entry-table th:nth-child(5) { width: 90px; }
        .structure-entry-table th:nth-child(6) { width: 80px; }

        .structure-table th:nth-child(1) { width: 130px; }
        .structure-table th:nth-child(2) { width: 420px; }
        .structure-table th:nth-child(3) { width: 220px; }
        .structure-table th:nth-child(4) { width: 110px; }
        .structure-table th:nth-child(5) { width: 110px; }

        .structure-row-phase td { background: #fff1dc; font-weight: 900; }
        .structure-row-layer td { background: #f8fbff; font-weight: 800; }
        .structure-row-task td { background: #ffffff; }
        .structure-row-sub_task td { background: #ffffff; color: #334155; }

        .structure-row-phase td:first-child { border-left: 4px solid var(--primary); }
        .structure-row-layer td:first-child { border-left: 4px solid var(--brand-blue); }
        .structure-row-task td:first-child { border-left: 4px solid #38bdf8; }
        .structure-row-sub_task td:first-child { border-left: 4px solid #94a3b8; }

        .structure-name {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .structure-indent {
            display: inline-block;
            flex: 0 0 auto;
            width: calc(var(--depth) * 26px);
        }

        .structure-type-pill {
            display: inline-flex;
            min-width: 72px;
            justify-content: center;
            padding: 5px 8px;
            border-radius: 999px;
            background: #e5f0fb;
            color: var(--brand-blue-dark);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .structure-delete-form {
            margin: 0;
        }

        .structure-actions {
            display: inline-flex;
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

        @media (max-width: 1100px) {
            .structure-guide {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .structure-guide-step::after {
                display: none;
            }
        }

        @media (max-width: 700px) {
            .structure-guide {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>{{ $project->name }} Structure</h1>
            <p>Step 2: Phase -> Layer -> Task -> Sub-task</p>
        </div>
        <div class="actions">
            <a class="btn secondary" href="{{ route('admin.projects.show', $project) }}">Back to Project</a>
            <a class="btn" href="{{ route('admin.projects.boq', $project) }}">Next: BOQ</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    @include('admin.projects.partials.workflow', ['project' => $project])

    <section class="structure-guide">
        <div class="structure-guide-step">
            <span>1</span>
            <strong>Phase</strong>
            <small>Main project stage, for example Civil Work or Tower A.</small>
        </div>
        <div class="structure-guide-step">
            <span>2</span>
            <strong>Layer</strong>
            <small>Area or layer inside the phase, for example UGT Work.</small>
        </div>
        <div class="structure-guide-step">
            <span>3</span>
            <strong>Task</strong>
            <small>Actual work activity connected with BOQ planning.</small>
        </div>
        <div class="structure-guide-step">
            <span>4</span>
            <strong>Sub-task</strong>
            <small>Small checklist item for daily execution and tracking.</small>
        </div>
    </section>

    <form class="card form-card" method="POST" action="{{ route('admin.projects.structure.store', $project) }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Structure Row</h2>
            <div class="structure-sheet">
                <table class="structure-entry-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Parent</th>
                            <th>Name</th>
                            <th>Area / Chainage</th>
                            <th>Sort</th>
                            <th>Add</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select id="structure_type" name="structure_type" required>
                                    @foreach ($structureTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select id="parent_task_id" name="parent_task_id">
                                    <option value="">No parent / Top level</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}">{{ $structureTypes[$item->structure_type] ?? 'Task' }} - {{ $item->title }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input id="title" name="title" type="text" required placeholder="Example: Phase 1 / Earthwork / Excavation"></td>
                            <td><input id="work_area" name="work_area" type="text" placeholder="Chainage, layer, location"></td>
                            <td><input name="sort_order" type="number" min="0"></td>
                            <td><button class="boq-icon-btn" type="submit">+</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </form>

    <div class="page-header section-spacer">
        <div>
            <h1>Working Structure</h1>
            <p>This structure is used before BOQ and planning.</p>
        </div>
    </div>

    <div class="card table-wrap structure-sheet">
        <table class="structure-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Structure Name</th>
                    <th>Area</th>
                    <th>Sort</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roots as $item)
                    @include('admin.projects.partials.structure-row', ['item' => $item, 'project' => $project, 'structureTypes' => $structureTypes, 'depth' => 0])
                @empty
                    <tr>
                        <td class="empty" colspan="5">No structure added yet. Add Phase first, then add Layer, Task, and Sub-task under it.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        var nextStructureType = {
            phase: 'layer',
            layer: 'task',
            task: 'sub_task',
            sub_task: 'sub_task'
        };

        document.querySelectorAll('[data-add-structure-child]').forEach(function (button) {
            button.addEventListener('click', function () {
                var parentId = button.getAttribute('data-parent-id') || '';
                var parentType = button.getAttribute('data-parent-type') || 'phase';
                var parentArea = button.getAttribute('data-parent-area') || '';
                var form = document.querySelector('form[action*="/structure"]');

                document.getElementById('parent_task_id').value = parentId;
                document.getElementById('structure_type').value = nextStructureType[parentType] || 'task';
                document.getElementById('work_area').value = parentArea;
                document.getElementById('title').focus();

                if (form) {
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
@endsection
