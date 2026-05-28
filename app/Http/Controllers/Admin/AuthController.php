<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if (
            $credentials['email'] !== $adminEmail
            || ! hash_equals($adminPassword, $credentials['password'])
        ) {
            return back()
                ->withErrors(['email' => 'Invalid admin login details.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_logged_in', true);
        $request->session()->put('admin_email', $adminEmail);
        $request->session()->put('admin_permissions', app(AdminNavigation::class)->configuredPermissions());

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_logged_in', 'admin_email', 'admin_permissions']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
