<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $constructKaro = Company::query()->firstOrCreate(
            ['slug' => 'constructkaro'],
            [
                'name' => 'ConstructKaro',
                'contact_email' => config('admin.email'),
                'status' => 'active',
            ]
        );

        $plan = SubscriptionPlan::query()->firstOrCreate(
            ['slug' => 'basic-monthly'],
            [
                'name' => 'Basic Monthly',
                'monthly_price' => 999,
                'employee_limit' => 50,
                'features' => [
                    'attendance',
                    'reports',
                    'labour_attendance',
                    'vehicles',
                    'payments',
                ],
            ]
        );

        CompanySubscription::query()->firstOrCreate(
            [
                'company_id' => $constructKaro->id,
                'subscription_plan_id' => $plan->id,
                'starts_at' => now()->startOfMonth()->toDateString(),
            ],
            [
                'status' => 'active',
                'ends_at' => now()->endOfMonth()->toDateString(),
                'amount' => $plan->monthly_price,
                'notes' => 'Default monthly subscription.',
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'company-admin@example.com'],
            [
                'company_id' => $constructKaro->id,
                'role' => 'company_admin',
                'is_active' => true,
                'name' => 'Company Admin',
                'mobile' => '9999999999',
                'password' => Hash::make('admin123456'),
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'hr-admin@example.com'],
            [
                'company_id' => $constructKaro->id,
                'role' => 'company_admin',
                'is_active' => true,
                'name' => 'HR Admin',
                'mobile' => '8888888888',
                'admin_permissions' => ['hr', 'dashboard'],
                'password' => Hash::make('hr123456'),
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'purchase-admin@example.com'],
            [
                'company_id' => $constructKaro->id,
                'role' => 'company_admin',
                'is_active' => true,
                'name' => 'Purchase Admin',
                'mobile' => '7777777777',
                'admin_permissions' => ['purchase', 'dashboard'],
                'password' => Hash::make('purchase123456'),
            ]
        );
    }
}
