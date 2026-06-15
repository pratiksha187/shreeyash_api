<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Company;
use App\Support\Tenant;
use App\Support\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $companySlug = $request->header('X-Company-Slug')
            ?: $request->input('company_slug')
            ?: $request->query('company_slug');

        if (! $companySlug) {
            return response()->json([
                'message' => 'Company slug is required.',
                'errors' => [
                    'company_slug' => ['Send company_slug or X-Company-Slug with every API request.'],
                ],
            ], 422);
        }

        if ($companySlug) {
            $company = Company::query()->where('slug', $companySlug)->first();

            if (! $company) {
                return response()->json(['message' => 'Company not found.'], 422);
            }

            if (! $company->hasActiveSubscription()) {
                return response()->json([
                    'message' => 'Company subscription is inactive or expired. Please renew your monthly plan.',
                ], 402);
            }

            if (! $company->database_name) {
                try {
                    app(TenantDatabaseManager::class)->provision($company);
                    $company->refresh();
                } catch (\Throwable $exception) {
                    return response()->json([
                        'message' => 'Company database could not be created. Please contact ConstructKaro admin.',
                        'error' => $exception->getMessage(),
                    ], 500);
                }
            }

            app(Tenant::class)->set($company);
        }

        $user = User::query()
            ->forCurrentCompany()
            ->employees()
            ->where('api_token', hash('sha256', $token))
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Your account is inactive.'], 403);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
