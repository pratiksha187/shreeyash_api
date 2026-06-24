@extends('admin.layouts.app')

@section('title', 'Labour Master | Admin Panel')
@section('headerTitle', 'Labour Master')
@section('headerSubtitle', 'Create and manage labour records')

@section('content')
    <div class="page-header">
        <div>
            <h1>Labour Master</h1>
            <p>Maintain labour names, mobile numbers, labour codes, and trades.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.labour-attendance.index') }}">View Attendance</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.labours.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Labour</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="contractor_id">Contractor</label>
                    <select id="contractor_id" name="contractor_id">
                        <option value="">Select Contractor</option>
                        @foreach ($contractors as $contractor)
                            <option value="{{ $contractor->id }}" @selected((string) old('contractor_id') === (string) $contractor->id)>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="labour_name">Labour Name</label>
                    <input id="labour_name" name="name" type="text" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="labour_mobile">Mobile</label>
                    <input id="labour_mobile" name="mobile" type="text" value="{{ old('mobile') }}">
                </div>
                <div class="field">
                    <label for="labour_code">Labour Code</label>
                    <input id="labour_code" name="labour_code" type="text" value="{{ old('labour_code') }}">
                </div>
                <div class="field">
                    <label for="trade">Trade</label>
                    <input id="trade" name="trade" type="text" value="{{ old('trade') }}">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Add Labour</button>
            </div>
        </section>
    </form>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Contractor</th>
                    <th>Labour Name</th>
                    <th>Mobile</th>
                    <th>Code</th>
                    <th>Trade</th>
                    <th>Status</th>
                    <th>Attendance</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($labours as $labour)
                    <tr>
                        <td>{{ $labour->id }}</td>
                        <td>{{ $labour->contractor?->name ?? '-' }}</td>
                        <td>{{ $labour->name }}</td>
                        <td>{{ $labour->mobile ?? '-' }}</td>
                        <td>{{ $labour->labour_code ?? '-' }}</td>
                        <td>{{ $labour->trade ?? '-' }}</td>
                        <td>
                            <span class="status-pill {{ $labour->is_active ? 'status-approved' : 'status-rejected' }}">
                                {{ $labour->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $labour->labour_attendances_count }}</td>
                        <td>{{ $labour->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn small secondary" href="{{ route('admin.labours.edit', $labour) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.labours.destroy', $labour) }}" onsubmit="return confirm('Delete this labour?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="10">No labours added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $labours->links('admin.pagination') }}
    </div>
@endsection
