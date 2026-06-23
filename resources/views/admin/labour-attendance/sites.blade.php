@extends('admin.layouts.app')

@section('title', 'Site Master | Admin Panel')
@section('headerTitle', 'Site Master')
@section('headerSubtitle', 'Create and manage labour sites')

@section('content')
    <div class="page-header">
        <div>
            <h1>Site Master</h1>
            <p>Maintain site names and addresses used by labour attendance.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.labour-attendance.index') }}">View Attendance</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.labour-sites.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Site</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="site_name">Site Name</label>
                    <input id="site_name" name="name" type="text" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="site_address">Address</label>
                    <input id="site_address" name="address" type="text" value="{{ old('address') }}">
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Add Site</button>
            </div>
        </section>
    </form>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Site Name</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Contractors</th>
                    <th>Attendance</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sites as $site)
                    <tr>
                        <td>{{ $site->id }}</td>
                        <td>{{ $site->name }}</td>
                        <td>{{ $site->address ?? '-' }}</td>
                        <td>
                            <span class="status-pill {{ $site->is_active ? 'status-approved' : 'status-rejected' }}">
                                {{ $site->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $site->contractors_count }}</td>
                        <td>{{ $site->labour_attendances_count }}</td>
                        <td>{{ $site->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn small secondary" href="{{ route('admin.labour-sites.edit', $site) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.labour-sites.destroy', $site) }}" onsubmit="return confirm('Delete this site?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger small" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="8">No sites added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $sites->links('admin.pagination') }}
    </div>
@endsection
