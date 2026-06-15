@extends('admin.layouts.app')

@section('title', 'Companies | Admin Panel')
@section('headerTitle', 'Companies')
@section('headerSubtitle', 'Create employer companies and manage monthly subscriptions')

@section('content')
    <div class="page-header">
        <div>
            <h1>Companies</h1>
            <p>ConstructKaro main admin can create employers, give login credentials, and renew monthly plans.</p>
        </div>
        <a class="btn" href="{{ route('admin.companies.create') }}">Add Company</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Users</th>
                    <th>Plan</th>
                    <th>Subscription</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                    @php($subscription = $company->activeSubscription)
                    <tr>
                        <td>
                            <a class="table-link" href="{{ route('admin.companies.show', $company) }}">
                                {{ $company->name }}
                            </a>
                            <div class="table-subtext">{{ $company->slug }} | DB: {{ $company->database_name ?? '-' }}</div>
                        </td>
                        <td>
                            {{ $company->contact_name ?? '-' }}
                            <div class="table-subtext">
                                {{ $company->contact_mobile ?? $company->contact_email ?? '-' }}
                            </div>
                        </td>
                        <td>{{ $company->users_count }}</td>
                        <td>{{ $subscription?->plan?->name ?? '-' }}</td>
                        <td>
                            @if ($subscription)
                                {{ $subscription->starts_at?->format('d M Y') }} to {{ $subscription->ends_at?->format('d M Y') }}
                                <div class="table-subtext">Rs. {{ number_format((float) $subscription->amount, 2) }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <span class="status-pill {{ $company->hasActiveSubscription() ? 'status-approved' : 'status-rejected' }}">
                                {{ $company->hasActiveSubscription() ? 'active' : 'expired' }}
                            </span>
                        </td>
                        <td>
                            <a class="btn small" href="{{ route('admin.companies.show', $company) }}">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No companies added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $companies->links() }}
    </div>
@endsection
