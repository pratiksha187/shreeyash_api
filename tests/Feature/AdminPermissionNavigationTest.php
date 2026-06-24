<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPermissionNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function prepareCompanyAdminContext(): Company
    {
        $company = Company::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'database_name' => 'test_company_db',
            'status' => 'active',
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'monthly_price' => 100,
            'employee_limit' => 10,
        ]);
        CompanySubscription::create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 100,
        ]);

        $baseConnection = config('database.connections.mysql');
        $baseConnection['database'] = $company->database_name;
        config(['database.connections.tenant' => $baseConnection]);
        DB::purge('tenant');
        app(\App\Support\Tenant::class)->set($company);

        if (! Schema::connection('tenant')->hasTable('users')) {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force' => true,
            ]);
        }

        return $company;
    }

    public function test_admin_sidebar_groups_links_and_hides_unpermitted_modules(): void
    {
        $company = $this->prepareCompanyAdminContext();

        $response = $this->withSession([
            'admin_logged_in' => true,
            'admin_email' => 'hr@example.com',
            'admin_permissions' => ['employees'],
            'admin_role' => 'company_admin',
            'admin_company_id' => $company->id,
            'tenant_database_ready' => true,
        ])->get('/admin/employees');

        $response->assertOk()
            ->assertSee('HR')
            ->assertSee('Employees')
            ->assertDontSee('Payments')
            ->assertDontSee('Vehicles');
    }

    public function test_admin_cannot_open_route_without_permission(): void
    {
        $company = $this->prepareCompanyAdminContext();

        $response = $this->withSession([
            'admin_logged_in' => true,
            'admin_email' => 'hr@example.com',
            'admin_permissions' => ['employees'],
            'admin_role' => 'company_admin',
            'admin_company_id' => $company->id,
            'tenant_database_ready' => true,
        ])->get('/admin/payments');

        $response->assertForbidden();
    }

    public function test_admin_can_approve_leave_request(): void
    {
        $company = $this->prepareCompanyAdminContext();

        $employee = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'employee',
            'name' => 'Jane Doe',
        ]);

        $leave = Attendance::create([
            'company_id' => $company->id,
            'user_id' => $employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'leave',
            'leave_approval_status' => 'pending',
            'leave_admin_note' => null,
        ]);

        $response = $this->withSession([
            'admin_logged_in' => true,
            'admin_email' => 'hr@example.com',
            'admin_permissions' => ['leave_requests'],
            'admin_role' => 'company_admin',
            'admin_company_id' => $company->id,
            'tenant_database_ready' => true,
        ])->patch('/admin/leave-requests/'.$leave->id, [
            'status' => 'approved',
            'admin_note' => 'Approved by admin',
        ]);

        $response->assertRedirect();
        $leave->refresh();
        $this->assertSame('approved', $leave->leave_approval_status);
    }
}
