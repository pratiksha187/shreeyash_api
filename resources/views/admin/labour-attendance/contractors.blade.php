@extends('admin.layouts.app')

@section('title', 'Contractor Master | Admin Panel')
@section('headerTitle', 'Contractor Master')
@section('headerSubtitle', 'Create and manage contractors')

@section('content')
    <div class="page-header">
        <div>
            <h1>Contractor Master</h1>
            <p>Maintain contractor names and mobile numbers used by the mobile app.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.labour-attendance.index') }}">View Attendance</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.contractors.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Contractor</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="contractor_name">Contractor Name</label>
                    <input id="contractor_name" name="name" type="text" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="contractor_mobile">Mobile</label>
                    <input id="contractor_mobile" name="mobile" type="text" value="{{ old('mobile') }}">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Add Contractor</button>
            </div>
        </section>
    </form>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Contractor Name</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Labours</th>
                    <th>Attendance</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contractors as $contractor)
                    <tr>
                        <td>{{ $contractor->id }}</td>
                        <td>{{ $contractor->name }}</td>
                        <td>{{ $contractor->mobile ?? '-' }}</td>
                        <td>
                            <span class="status-pill {{ $contractor->is_active ? 'status-approved' : 'status-rejected' }}">
                                {{ $contractor->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $contractor->labours_count }}</td>
                        <td>{{ $contractor->labour_attendances_count }}</td>
                        <td>{{ $contractor->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn small secondary" href="{{ route('admin.contractors.edit', $contractor) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.contractors.destroy', $contractor) }}" onsubmit="return confirm('Delete this contractor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="8">No contractors added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $contractors->links('admin.pagination') }}
    </div>
@endsection
