<?php

namespace App\Http\Middleware;

use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = app(Tenant::class)->company();

        if ($company && ! $company->hasActiveSubscription()) {
            return response()->json([
                'message' => 'Company subscription is inactive or expired. Please renew your monthly plan.',
            ], 402);
        }

        return $next($request);
    }
}
