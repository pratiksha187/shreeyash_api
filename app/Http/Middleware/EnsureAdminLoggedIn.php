<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AdminNavigation;
use App\Support\Tenant;
use App\Support\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminLoggedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $this->hydrateAdminSession($request);

        if (
            $request->session()->get('admin_role') !== 'super_admin'
            && ! $request->session()->has('admin_company_id')
        ) {
            $this->clearAdminSession($request);

            return redirect()
                ->route('admin.login')
                ->with('error', 'Please login with an employer/company admin account to manage employees.');
        }

        if ($request->session()->has('admin_company_id')) {
            $company = \App\Models\Company::query()->find($request->session()->get('admin_company_id'));

            if (! $company) {
                $this->clearAdminSession($request);

                return redirect()
                    ->route('admin.login')
                    ->with('error', 'Employer company was not found. Please login again.');
            }

            if (! $company->hasActiveSubscription()) {
                $this->clearAdminSession($request);

                return redirect()
                    ->route('admin.login')
                    ->with('error', 'This company subscription is inactive or expired.');
            }

            if ($request->session()->get('admin_role') !== 'company_admin') {
                $request->session()->put('admin_role', 'company_admin');
                $request->session()->put('admin_permissions', $this->adminPermissions($request));
            }

            if (
                $request->session()->get('admin_role') === 'company_admin'
                && ! $request->session()->get('tenant_database_ready')
                && ! $company->database_name
            ) {
                app(TenantDatabaseManager::class)->provision($company);
                $request->session()->put('tenant_database_ready', true);
                $company->refresh();
            } elseif (! $company->database_name) {
                app(TenantDatabaseManager::class)->provision($company);
                $request->session()->put('tenant_database_ready', true);
                $company->refresh();
            }

            if ($company->database_name) {
                $request->session()->put('tenant_database_ready', true);
            }

            app(Tenant::class)->set($company);
        }

        $permission = app(AdminNavigation::class)->permissionForRoute($request->route()?->getName());

        if (
            $request->session()->get('admin_role') === 'super_admin'
            && $permission
            && ! in_array($permission, config('admin.super_admin_permissions', []), true)
        ) {
            return redirect()
                ->route('admin.companies.index')
                ->with('error', 'ConstructKaro super admin cannot add employees directly. Login with the employer admin account to add that company employees.');
        }

        if ($request->session()->get('admin_role') === 'company_admin' && $permission === 'companies') {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Employer admin cannot access ConstructKaro company management.');
        }

        if ($permission && ! app(AdminNavigation::class)->can($permission)) {
            abort(403);
        }

        return $next($request);
    }

    private function hydrateAdminSession(Request $request): void
    {
        if ($request->session()->get('admin_role') === 'super_admin' || $request->session()->has('admin_company_id')) {
            return;
        }

        $adminEmail = $request->session()->get('admin_email');

        if (! $adminEmail) {
            return;
        }

        if ($adminEmail === config('admin.email')) {
            $request->session()->put('admin_role', 'super_admin');
            $request->session()->put('admin_permissions', config('admin.super_admin_permissions', ['dashboard', 'companies']));

            return;
        }

        $admin = User::query()
            ->where('email', $adminEmail)
            ->where('role', 'company_admin')
            ->first();

        if (! $admin || ! $admin->company_id) {
            return;
        }

        $request->session()->put('admin_user_id', $admin->id);
        $request->session()->put('admin_company_id', $admin->company_id);
        $request->session()->put('admin_role', 'company_admin');
        $request->session()->put('admin_permissions', $admin->resolvedAdminPermissions());
    }

    private function adminPermissions(Request $request): array
    {
        $adminId = $request->session()->get('admin_user_id');

        if (! $adminId) {
            return config('admin.company_admin_permissions', []);
        }

        $admin = User::query()
            ->where('role', 'company_admin')
            ->find($adminId);

        return $admin?->resolvedAdminPermissions() ?? config('admin.company_admin_permissions', []);
    }

    private function clearAdminSession(Request $request): void
    {
        $request->session()->forget([
            'admin_logged_in',
            'admin_email',
            'admin_user_id',
            'admin_company_id',
            'admin_role',
            'tenant_database_ready',
            'admin_permissions',
        ]);
    }
}
