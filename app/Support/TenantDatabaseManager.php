<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantDatabaseManager
{
    public function databaseNameForSlug(string $slug): string
    {
        $safeSlug = preg_replace('/[^a-z0-9_]+/', '_', strtolower($slug)) ?: 'company';
        $safeSlug = trim($safeSlug, '_') ?: 'company';
        $year = now()->format('Y');

        return $safeSlug.'_constructkaro_'.$year;
    }

    public function configure(Company $company): void
    {
        if (! $company->database_name) {
            return;
        }

        $this->ensureDatabaseExists($company->database_name);

        $baseConnection = config('database.default') === 'sqlite' ? 'mysql' : config('database.default');
        $config = config("database.connections.$baseConnection");
        $config['database'] = $company->database_name;

        Config::set('database.connections.tenant', $config);
        DB::purge('tenant');
    }

    public function provision(Company $company): void
    {
        $company = $this->ensureDatabaseName($company);

        $this->createDatabase($company->database_name);
        $this->configure($company);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--force' => true,
        ]);

        $this->syncCompany($company);
        $this->syncSubscriptionData($company);
        $this->syncCompanyAdmins($company);

        DB::purge('tenant');
    }

    public function ensureDatabaseName(Company $company): Company
    {
        if ($company->database_name) {
            return $company;
        }

        $company->forceFill([
            'database_name' => $this->databaseNameForSlug($company->slug),
        ])->save();

        return $company->refresh();
    }

    public function ensureDatabaseExists(string $databaseName): void
    {
        $this->createDatabase($databaseName);
    }

    private function createDatabase(string $databaseName): void
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $databaseName)) {
            throw new \InvalidArgumentException('Invalid tenant database name.');
        }

        $charset = config('database.connections.mysql.charset', 'utf8mb4');
        $collation = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');

        DB::statement(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s',
            str_replace('`', '``', $databaseName),
            $charset,
            $collation
        ));
    }

    private function syncCompany(Company $company): void
    {
        DB::connection('tenant')->table('companies')->updateOrInsert(
            ['id' => $company->id],
            [
                'name' => $company->name,
                'slug' => $company->slug,
                'database_name' => $company->database_name,
                'contact_name' => $company->contact_name,
                'contact_email' => $company->contact_email,
                'contact_mobile' => $company->contact_mobile,
                'status' => $company->status,
                'trial_ends_at' => $company->trial_ends_at,
                'created_at' => $company->created_at,
                'updated_at' => now(),
            ]
        );
    }

    private function syncSubscriptionData(Company $company): void
    {
        foreach (\App\Models\SubscriptionPlan::query()->get() as $plan) {
            DB::connection('tenant')->table('subscription_plans')->updateOrInsert(
                ['id' => $plan->id],
                [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'monthly_price' => $plan->monthly_price,
                    'employee_limit' => $plan->employee_limit,
                    'features' => $plan->features ? json_encode($plan->features) : null,
                    'is_active' => $plan->is_active,
                    'created_at' => $plan->created_at,
                    'updated_at' => now(),
                ]
            );
        }

        foreach ($company->subscriptions()->get() as $subscription) {
            DB::connection('tenant')->table('company_subscriptions')->updateOrInsert(
                ['id' => $subscription->id],
                [
                    'company_id' => $subscription->company_id,
                    'subscription_plan_id' => $subscription->subscription_plan_id,
                    'status' => $subscription->status,
                    'starts_at' => $subscription->starts_at,
                    'ends_at' => $subscription->ends_at,
                    'amount' => $subscription->amount,
                    'payment_reference' => $subscription->payment_reference,
                    'notes' => $subscription->notes,
                    'created_at' => $subscription->created_at,
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function syncCompanyAdmins(Company $company): void
    {
        $admins = DB::table('users')
            ->where('company_id', $company->id)
            ->where('role', 'company_admin')
            ->get();

        foreach ($admins as $admin) {
            DB::connection('tenant')->table('users')->updateOrInsert(
                ['id' => $admin->id],
                [
                    'company_id' => $company->id,
                    'role' => 'company_admin',
                    'is_active' => $admin->is_active,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'mobile' => $admin->mobile,
                    'password' => $admin->password,
                    'email_verified_at' => $admin->email_verified_at,
                    'remember_token' => $admin->remember_token,
                    'created_at' => $admin->created_at,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
