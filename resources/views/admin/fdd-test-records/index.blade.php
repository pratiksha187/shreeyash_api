@extends('admin.layouts.app')

@section('title', 'FDD Test Records | Admin Panel')
@section('headerTitle', 'FDD Test Records')
@section('headerSubtitle', 'Road-wise material testing register')

@section('content')
    <div class="page-header">
        <div>
            <h1>FDD Test Records</h1>
            <p>Add and review FDD testing entries in the same road-wise format as the site sheet.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.fdd-test-records.export', request()->query()) }}">Export CSV</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.fdd-road-sections.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Road / Section Master</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="master_group_number">Sr. No.</label>
                    <input id="master_group_number" name="group_number" type="number" min="1" value="{{ old('group_number') }}" required>
                    @error('group_number')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="master_name">Road / Section Name</label>
                    <input id="master_name" name="name" value="{{ old('name') }}" placeholder="Road No. 1" required>
                    @error('name')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Add Road</button>
                </div>
            </div>
        </section>
    </form>

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.fdd-test-records.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add FDD Entry</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="fdd_road_section_id">Road / Section</label>
                    <select id="fdd_road_section_id" name="fdd_road_section_id" required>
                        <option value="">Select Road / Section</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('fdd_road_section_id') === (string) $section->id)>
                                {{ $section->group_number }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('fdd_road_section_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="test_date">Date</label>
                    <input id="test_date" name="test_date" type="date" value="{{ old('test_date') }}">
                    @error('test_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="material">Material</label>
                    <input id="material" name="material" list="material-options" value="{{ old('material') }}" placeholder="WMM" required>
                    <datalist id="material-options">
                        @foreach ($materials as $material)
                            <option value="{{ $material }}">
                        @endforeach
                    </datalist>
                    @error('material')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="sort_order">Row Order</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order') }}" placeholder="Auto">
                    @error('sort_order')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Add Record</button>
                </div>

                <div class="field full">
                    <label for="location">Location</label>
                    <textarea id="location" name="location" required placeholder="CH 4 9 Mtr Road No. 1 Paver Block WMM Testing">{{ old('location') }}</textarea>
                    @error('location')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>
    </form>

    <form class="card form-card report-filter" method="GET" action="{{ route('admin.fdd-test-records.index') }}">
        <section class="form-section">
            <h2 class="section-title">Filters</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="from_date">From Date</label>
                    <input id="from_date" name="from_date" type="date" value="{{ old('from_date', $filters['from_date'] ?? '') }}">
                    @error('from_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="to_date">To Date</label>
                    <input id="to_date" name="to_date" type="date" value="{{ old('to_date', $filters['to_date'] ?? '') }}">
                    @error('to_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="filter_section_name">Road / Section</label>
                    <select id="filter_section_name" name="road_section_id">
                        <option value="">All Sections</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) ($filters['road_section_id'] ?? '') === (string) $section->id)>
                                {{ $section->group_number }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('road_section_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="filter_material">Material</label>
                    <select id="filter_material" name="material">
                        <option value="">All Materials</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material }}" @selected(($filters['material'] ?? '') === $material)>{{ $material }}</option>
                        @endforeach
                    </select>
                    @error('material')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Apply Filter</button>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <a class="btn secondary" href="{{ route('admin.fdd-test-records.index') }}">Clear</a>
                </div>
            </div>
        </section>
    </form>

    <section class="stats-grid">
        <div class="card stat-card">
            <span>Total Records</span>
            <strong>{{ $summary['total_records'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Road Sections</span>
            <strong>{{ $summary['sections'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Materials</span>
            <strong>{{ $summary['materials'] }}</strong>
        </div>
        <div class="card stat-card">
            <span>Latest Test</span>
            <strong>{{ $summary['latest_date'] ? $summary['latest_date']->format('d M') : '-' }}</strong>
        </div>
    </section>

    <div class="card table-wrap">
        <div class="fdd-report-title">FDD Test Record</div>
        <table class="fdd-table">
            <thead>
                <tr>
                    <th>Sr. No.</th>
                    <th>Date</th>
                    <th>Material</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groupedRecords as $group)
                    @php($first = $group->first())
                    <tr class="fdd-section-row">
                        <td class="fdd-sr">{{ $first->roadSection?->group_number ?? $first->group_number }}</td>
                        <td class="fdd-section-name" colspan="3">{{ $first->roadSection?->name ?? $first->section_name }}</td>
                        <td></td>
                    </tr>

                    @foreach ($group as $record)
                        <tr>
                            <td></td>
                            <td class="fdd-date">{{ $record->test_date?->format('d-m-Y') ?? '-' }}</td>
                            <td class="fdd-material">{{ $record->material }}</td>
                            <td>{{ $record->location }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn secondary small" href="{{ route('admin.fdd-test-records.edit', $record) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.fdd-test-records.destroy', $record) }}" onsubmit="return confirm('Delete this FDD record?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn danger small" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td class="empty" colspan="5">No FDD test records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
