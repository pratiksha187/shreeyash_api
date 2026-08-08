@extends('admin.layouts.app')

@section('title', $company->name.' | Admin Panel')
@section('headerTitle', $company->name)
@section('headerSubtitle', 'Company login, users, and monthly subscription')
@section('bodyClass', 'company-show-page')

@section('content')
    <style>
        body.company-show-page {
            overflow-x: hidden;
        }

        body.company-show-page .main {
            max-width: 1320px;
        }

        .company-users-table {
            table-layout: fixed;
        }

        .company-users-table th,
        .company-users-table td {
            vertical-align: top;
        }

        .company-users-table th:nth-child(1),
        .company-users-table td:nth-child(1) {
            width: 14%;
        }

        .company-users-table th:nth-child(2),
        .company-users-table td:nth-child(2) {
            width: 20%;
        }

        .company-users-table th:nth-child(3),
        .company-users-table td:nth-child(3) {
            width: 12%;
        }

        .company-users-table th:nth-child(4),
        .company-users-table td:nth-child(4) {
            width: 12%;
        }

        .company-users-table th:nth-child(5),
        .company-users-table td:nth-child(5) {
            width: 34%;
        }

        .company-users-table th:nth-child(6),
        .company-users-table td:nth-child(6) {
            width: 8%;
        }

        .company-user-meta {
            display: grid;
            gap: 3px;
            min-width: 0;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .module-permission-form {
            display: grid;
            gap: 12px;
            min-width: 0;
        }

        .module-permission-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .module-permission-toolbar .btn {
            min-height: 32px;
            padding: 6px 10px;
            font-size: 12px;
        }

        .module-permission-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .module-permission-grid .checkbox-option {
            min-height: 40px;
            padding: 9px 10px;
            border: 1px solid #cfe0f3;
            border-radius: 8px;
            background: #f8fbff;
            line-height: 1.25;
        }

        .module-permission-grid .checkbox-option span {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .module-permission-grid input[type="checkbox"] {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
        }

        @media (max-width: 1100px) {
            .company-users-table {
                min-width: 980px;
            }

            .company-users-wrap {
                overflow-x: auto;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>{{ $company->name }}</h1>
            <p>Employer admin uses the normal admin login page with their email and password.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.companies.index') }}">Back to Companies</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="detail-grid">
        <div class="card detail-item">
            <span>Status</span>
            <strong>{{ $company->status }}</strong>
        </div>
        <div class="card detail-item">
            <span>Subscription</span>
            <strong>{{ $company->hasActiveSubscription() ? 'Active' : 'Expired' }}</strong>
        </div>
        <div class="card detail-item">
            <span>Total Users</span>
            <strong>{{ $company->users->count() }}</strong>
        </div>
        <div class="card detail-item">
            <span>Slug</span>
            <strong>{{ $company->slug }}</strong>
        </div>
        <div class="card detail-item">
            <span>Database</span>
            <strong>{{ $company->database_name ?? '-' }}</strong>
        </div>
    </div>

    <div class="sheet-summary-grid">
        <form class="card form-card" method="POST" action="{{ route('admin.companies.database', $company) }}">
            @csrf
            <section class="form-section">
                <h2 class="section-title">Separate Database</h2>
                <p>Employer employees and work data are stored in this separate database after employer login.</p>
                <div class="detail-grid" style="grid-template-columns: 1fr;">
                    <div class="card detail-item">
                        <span>Database Name</span>
                        <strong>{{ $company->database_name ?? 'Not created yet' }}</strong>
                    </div>
                </div>
                @if (! $company->database_name)
                    <div class="actions">
                        <button class="btn" type="submit">Create Database</button>
                    </div>
                @else
                    <p>Database already created. No sync action is needed.</p>
                @endif
            </section>
        </form>

        <form class="card form-card" method="POST" action="{{ route('admin.companies.renew', $company) }}">
            @csrf
            <section class="form-section">
                <h2 class="section-title">Renew Monthly Plan</h2>
                <div class="form-grid">
                    <div class="field">
                        <label for="subscription_plan_id">Plan</label>
                        <select id="subscription_plan_id" name="subscription_plan_id" required>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">
                                    {{ $plan->name }} - Rs. {{ number_format((float) $plan->monthly_price, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('subscription_plan_id') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="amount">Amount</label>
                        <input id="amount" name="amount" type="number" min="0" step="0.01">
                        @error('amount') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="starts_at">Start Date</label>
                        <input id="starts_at" name="starts_at" type="date" value="{{ $defaultStartDate }}" required>
                        @error('starts_at') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="ends_at">End Date</label>
                        <input id="ends_at" name="ends_at" type="date" value="{{ $defaultEndDate }}" required>
                        @error('ends_at') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field full">
                        <label for="payment_reference">Payment Reference</label>
                        <input id="payment_reference" name="payment_reference" type="text">
                        @error('payment_reference') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field full">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes"></textarea>
                        @error('notes') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="actions">
                    <button class="btn" type="submit">Renew Subscription</button>
                </div>
            </section>
        </form>

        <form class="card form-card" method="POST" action="{{ route('admin.companies.status', $company) }}">
            @csrf
            @method('PATCH')
            <section class="form-section">
                <h2 class="section-title">Company Status</h2>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active" @selected($company->status === 'active')>Active</option>
                        <option value="inactive" @selected($company->status === 'inactive')>Inactive</option>
                        <option value="suspended" @selected($company->status === 'suspended')>Suspended</option>
                    </select>
                    @error('status') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="actions">
                    <button class="btn secondary" type="submit">Update Status</button>
                </div>
            </section>
        </form>
    </div>

    <div class="page-header section-spacer">
        <div>
            <h1>Employer Login Users</h1>
            <p>Company admins can login to the same admin panel. Employees login from the app/API with mobile and password.</p>
        </div>
    </div>

    <div class="card table-wrap company-users-wrap">
        <table class="company-users-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Role</th>
                    <th>Modules</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($company->users as $user)
                    <tr>
                        <td><div class="company-user-meta">{{ $user->name }}</div></td>
                        <td><div class="company-user-meta">{{ $user->email }}</div></td>
                        <td><div class="company-user-meta">{{ $user->mobile ?? '-' }}</div></td>
                        <td><div class="company-user-meta">{{ str_replace('_', ' ', $user->role) }}</div></td>
                        <td>
                            @if ($user->role === 'company_admin')
                                <form class="module-permission-form" method="POST" action="{{ route('admin.companies.users.permissions', [$company, $user]) }}">
                                    @csrf
                                    @method('PATCH')
                                    @php
                                        $selectedPermissions = $user->resolvedAdminPermissions();
                                        $permissionGroups = [
                                            'hr' => ['employees', 'attendance_reports', 'missed_requests', 'leave_requests', 'labour_attendance', 'labour_costing', 'driver_attendance', 'site_master', 'contractor_master', 'labour_master', 'supplier_master', 'unit_master', 'payments', 'dpr_reports', 'challans', 'complaints', 'site_reports'],
                                            'engg' => ['project_management', 'site_reports', 'dpr_reports', 'fdd_test_records', 'mir_file_reports', 'complaints'],
                                            'purchase' => ['diesel_purchases', 'machinery_diesel_logs', 'product_purchases', 'purchase_orders', 'material_stock', 'supplier_master', 'unit_master', 'vehicle_maintenance'],
                                        ];
                                    @endphp
                                    <div class="module-permission-toolbar">
                                        <button class="btn secondary small" type="button" data-permission-select="all">Select All</button>
                                        <button class="btn secondary small" type="button" data-permission-select="none">Clear All</button>
                                    </div>
                                    <div class="module-permission-grid">
                                        @foreach ($modulePermissions as $permissionKey => $permissionLabel)
                                            @php
                                                $parentGroups = collect($permissionGroups)
                                                    ->filter(fn ($children) => in_array($permissionKey, $children, true))
                                                    ->keys()
                                                    ->implode(' ');
                                            @endphp
                                            <label class="checkbox-option">
                                                <input
                                                    type="checkbox"
                                                    name="admin_permissions[]"
                                                    value="{{ $permissionKey }}"
                                                    @if (array_key_exists($permissionKey, $permissionGroups)) data-permission-group="{{ $permissionKey }}" @endif
                                                    @if ($parentGroups !== '') data-permission-parents="{{ $parentGroups }}" @endif
                                                    @checked(in_array($permissionKey, $selectedPermissions, true))
                                                >
                                                <span>{{ $permissionLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <button class="btn small" type="submit">Save Modules</button>
                                </form>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="6">No users yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="page-header section-spacer">
        <div>
            <h1>Subscription History</h1>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Dates</th>
                    <th>Amount</th>
                    <th>Reference</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($company->subscriptions->sortByDesc('starts_at') as $subscription)
                    <tr>
                        <td>{{ $subscription->plan?->name ?? '-' }}</td>
                        <td>{{ $subscription->starts_at?->format('d M Y') }} to {{ $subscription->ends_at?->format('d M Y') }}</td>
                        <td>Rs. {{ number_format((float) $subscription->amount, 2) }}</td>
                        <td>{{ $subscription->payment_reference ?? '-' }}</td>
                        <td>{{ $subscription->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="5">No subscriptions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-permission-select]');
            if (!button) {
                return;
            }

            const form = button.closest('.module-permission-form');
            if (!form) {
                return;
            }

            const checked = button.dataset.permissionSelect === 'all';
            form.querySelectorAll('input[name="admin_permissions[]"]').forEach((input) => {
                input.checked = checked;
            });
        });

        document.addEventListener('change', (event) => {
            const groupCheckbox = event.target.closest('[data-permission-group]');
            if (!groupCheckbox) {
                return;
            }

            const form = groupCheckbox.closest('.module-permission-form');
            const group = groupCheckbox.dataset.permissionGroup;

            form.querySelectorAll('[data-permission-parents]').forEach((input) => {
                const parents = (input.dataset.permissionParents || '').split(' ');
                if (parents.includes(group)) {
                    input.checked = groupCheckbox.checked;
                }
            });
        });
    </script>
@endsection
