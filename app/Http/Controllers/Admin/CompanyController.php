<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\TenantDatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(): View
    {
        $this->ensureSuperAdmin();

        $companies = Company::query()
            ->with(['activeSubscription.plan'])
            ->withCount('users')
            ->latest()
            ->paginate(15);

        return view('admin.companies.index', [
            'companies' => $companies,
        ]);
    }

    public function create(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.companies.create', [
            'plans' => $this->plans(),
            'modulePermissions' => config('admin.module_permissions', []),
            'defaultAdminPermissions' => config('admin.company_admin_permissions', []),
            'defaultStartDate' => today()->toDateString(),
            'defaultEndDate' => today()->endOfMonth()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:companies,slug'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_mobile' => ['nullable', 'string', 'max:20'],
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'admin_password' => ['required', 'string', 'min:6', 'confirmed'],
            'admin_permissions' => ['nullable', 'array'],
            'admin_permissions.*' => ['required', Rule::in(array_keys(config('admin.module_permissions', [])))],
        ]);

        $plan = SubscriptionPlan::query()->findOrFail($data['subscription_plan_id']);
        $slug = $data['slug'] ?: Str::slug($data['name']);
        $slug = $this->uniqueSlug($slug);
        $databaseName = app(TenantDatabaseManager::class)->databaseNameForSlug($slug);

        $company = Company::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'database_name' => $databaseName,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_mobile' => $data['contact_mobile'] ?? null,
            'status' => 'active',
        ]);

        CompanySubscription::query()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'amount' => $data['amount'] ?? $plan->monthly_price,
            'payment_reference' => $data['payment_reference'] ?? null,
            'notes' => 'Created from ConstructKaro admin panel.',
        ]);

        User::query()->create([
            'company_id' => $company->id,
            'role' => 'company_admin',
            'is_active' => true,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'mobile' => $data['admin_mobile'],
            'admin_permissions' => $data['admin_permissions'] ?? config('admin.company_admin_permissions', []),
            'password' => Hash::make($data['admin_password']),
        ]);

        app(TenantDatabaseManager::class)->provision($company);

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', 'Company, monthly subscription, and employer login created successfully.');
    }

    public function show(Company $company): View
    {
        $this->ensureSuperAdmin();

        $company->load(['subscriptions.plan', 'users' => fn ($query) => $query->latest()]);

        return view('admin.companies.show', [
            'company' => $company,
            'plans' => $this->plans(),
            'modulePermissions' => config('admin.module_permissions', []),
            'defaultStartDate' => today()->toDateString(),
            'defaultEndDate' => today()->endOfMonth()->toDateString(),
        ]);
    }

    public function renew(Request $request, Company $company): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $plan = SubscriptionPlan::query()->findOrFail($data['subscription_plan_id']);

        CompanySubscription::query()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'amount' => $data['amount'] ?? $plan->monthly_price,
            'payment_reference' => $data['payment_reference'] ?? null,
            'notes' => $data['notes'] ?? 'Renewed from ConstructKaro admin panel.',
        ]);

        $company->update(['status' => 'active']);

        return back()->with('success', 'Monthly subscription renewed successfully.');
    }

    public function provisionDatabase(Company $company): RedirectResponse
    {
        $this->ensureSuperAdmin();

        if ($company->database_name) {
            return back()->with('success', 'Company database already exists. No sync needed.');
        }

        if (! $company->database_name) {
            $company->update([
                'database_name' => app(TenantDatabaseManager::class)->databaseNameForSlug($company->slug),
            ]);
        }

        app(TenantDatabaseManager::class)->provision($company->refresh());

        return back()->with('success', 'Company database created successfully.');
    }

    public function updateStatus(Request $request, Company $company): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $company->update($data);

        return back()->with('success', 'Company status updated successfully.');
    }

    public function updateUserPermissions(Request $request, Company $company, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        abort_unless((int) $user->company_id === (int) $company->id && $user->role === 'company_admin', 404);

        $data = $request->validate([
            'admin_permissions' => ['nullable', 'array'],
            'admin_permissions.*' => ['required', Rule::in(array_keys(config('admin.module_permissions', [])))],
        ]);

        $user->update([
            'admin_permissions' => array_values($data['admin_permissions'] ?? []),
        ]);

        return back()->with('success', 'Admin module permissions updated successfully.');
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(session('admin_role') === 'super_admin', 403);
    }

    private function plans()
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('monthly_price')
            ->get();
    }

    private function uniqueSlug(string $slug): string
    {
        $slug = $slug ?: 'company';
        $original = $slug;
        $counter = 2;

        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
