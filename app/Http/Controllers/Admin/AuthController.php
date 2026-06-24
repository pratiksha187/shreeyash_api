<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminNavigation;
use App\Support\TenantDatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $adminEmail = config('admin.email');
        $adminPassword = config('admin.password');

        if ($credentials['email'] === $adminEmail && hash_equals($adminPassword, $credentials['password'])) {
            $request->session()->regenerate();
            $request->session()->put('admin_logged_in', true);
            $request->session()->put('admin_email', $adminEmail);
            $request->session()->put('admin_role', 'super_admin');
            $request->session()->forget(['admin_user_id', 'admin_company_id', 'tenant_database_ready']);
            $request->session()->put('admin_permissions', config('admin.super_admin_permissions', ['dashboard', 'companies']));

            return redirect()->route('admin.dashboard');
        }

        $admin = User::query()
            ->with('company')
            ->where('email', $credentials['email'])
            ->where('role', 'company_admin')
            ->first();

        if (
            ! $admin
            || ! $admin->is_active
            || ! Hash::check($credentials['password'], $admin->password)
        ) {
            return back()
                ->withErrors(['email' => 'Invalid admin login details.'])
                ->onlyInput('email');
        }

        if (! $admin->company_id || ! $admin->company) {
            return back()
                ->withErrors(['email' => 'This employer admin is not linked to any company. Please create the company from ConstructKaro admin panel.'])
                ->onlyInput('email');
        }

        if (! $admin->company?->hasActiveSubscription()) {
            return back()
                ->withErrors(['email' => 'This company subscription is inactive or expired.'])
                ->onlyInput('email');
        }

        if (! $admin->company->database_name) {
            try {
                app(TenantDatabaseManager::class)->provision($admin->company);
            } catch (\Throwable $exception) {
                return back()
                    ->withErrors(['email' => 'Company database could not be created. Please check MySQL database permissions. '.$exception->getMessage()])
                    ->onlyInput('email');
            }
        }

        $admin->load('company');

        $request->session()->regenerate();
        $request->session()->put('admin_logged_in', true);
        $request->session()->put('admin_email', $admin->email);
        $request->session()->put('admin_user_id', $admin->id);
        $request->session()->put('admin_company_id', $admin->company_id);
        $request->session()->put('admin_role', 'company_admin');
        $request->session()->put('tenant_database_ready', true);
        $request->session()->put('admin_permissions', $admin->resolvedAdminPermissions());

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
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
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
