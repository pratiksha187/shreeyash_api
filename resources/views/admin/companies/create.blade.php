@extends('admin.layouts.app')

@section('title', 'Add Company | Admin Panel')
@section('headerTitle', 'Add Company')
@section('headerSubtitle', 'Create employer company, subscription, and admin login')

@section('content')
    <div class="page-header">
        <div>
            <h1>Add Employer Company</h1>
            <p>This creates the company account and the employer admin login in one step.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.companies.index') }}">Back to Companies</a>
    </div>

    @if ($errors->any())
        <div class="alert-error">Please fix the highlighted fields and try again.</div>
    @endif

    <form class="card form-card" method="POST" action="{{ route('admin.companies.store') }}">
        @csrf

        <section class="form-section">
            <h2 class="section-title">Company Details</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="name">Company Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    @error('name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" value="{{ old('slug') }}" placeholder="auto generated if empty">
                    @error('slug') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="contact_name">Contact Person</label>
                    <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name') }}">
                    @error('contact_name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="contact_mobile">Contact Mobile</label>
                    <input id="contact_mobile" name="contact_mobile" type="text" value="{{ old('contact_mobile') }}">
                    @error('contact_mobile') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field full">
                    <label for="contact_email">Contact Email</label>
                    <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email') }}">
                    @error('contact_email') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="section-title">Monthly Subscription</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="subscription_plan_id">Plan</label>
                    <select id="subscription_plan_id" name="subscription_plan_id" required>
                        <option value="">Select plan</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('subscription_plan_id') == $plan->id)>
                                {{ $plan->name }} - Rs. {{ number_format((float) $plan->monthly_price, 2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('subscription_plan_id') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="amount">Amount</label>
                    <input id="amount" name="amount" type="number" min="0" step="0.01" value="{{ old('amount') }}" placeholder="use plan price if empty">
                    @error('amount') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="starts_at">Start Date</label>
                    <input id="starts_at" name="starts_at" type="date" value="{{ old('starts_at', $defaultStartDate) }}" required>
                    @error('starts_at') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="ends_at">End Date</label>
                    <input id="ends_at" name="ends_at" type="date" value="{{ old('ends_at', $defaultEndDate) }}" required>
                    @error('ends_at') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field full">
                    <label for="payment_reference">Payment Reference</label>
                    <input id="payment_reference" name="payment_reference" type="text" value="{{ old('payment_reference') }}" placeholder="cash, UPI, transaction id, invoice no">
                    @error('payment_reference') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="section-title">Employer Admin Login</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="admin_name">Admin Name</label>
                    <input id="admin_name" name="admin_name" type="text" value="{{ old('admin_name') }}" required>
                    @error('admin_name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="admin_mobile">Admin Mobile</label>
                    <input id="admin_mobile" name="admin_mobile" type="text" value="{{ old('admin_mobile') }}" required>
                    @error('admin_mobile') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="admin_email">Admin Email</label>
                    <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required>
                    @error('admin_email') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field"></div>

                <div class="field">
                    <label for="admin_password">Password</label>
                    <input id="admin_password" name="admin_password" type="password" required>
                    @error('admin_password') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="admin_password_confirmation">Confirm Password</label>
                    <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" required>
                </div>

                <div class="field full">
                    <label>Admin Modules</label>
                    @php($selectedPermissions = old('admin_permissions', $defaultAdminPermissions))
                    <div class="checkbox-grid">
                        @foreach ($modulePermissions as $permissionKey => $permissionLabel)
                            <label class="checkbox-option">
                                <input type="checkbox" name="admin_permissions[]" value="{{ $permissionKey }}" @checked(in_array($permissionKey, $selectedPermissions, true))>
                                <span>{{ $permissionLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('admin_permissions') <div class="error">{{ $message }}</div> @enderror
                    @error('admin_permissions.*') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Create Company Login</button>
            <a class="btn secondary" href="{{ route('admin.companies.index') }}">Cancel</a>
        </div>
    </form>
@endsection
