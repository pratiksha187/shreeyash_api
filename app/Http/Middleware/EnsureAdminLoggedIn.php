<?php

namespace App\Http\Middleware;

use App\Support\AdminNavigation;
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

        $permission = app(AdminNavigation::class)->permissionForRoute($request->route()?->getName());

        if ($permission && ! app(AdminNavigation::class)->can($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
