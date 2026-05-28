<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_groups_links_and_hides_unpermitted_modules(): void
    {
        User::factory()->create();

        $response = $this->withSession([
            'admin_logged_in' => true,
            'admin_email' => 'hr@example.com',
            'admin_permissions' => ['employees'],
        ])->get('/admin/employees');

        $response->assertOk()
            ->assertSee('HR')
            ->assertSee('Employees')
            ->assertDontSee('Payments')
            ->assertDontSee('Vehicles');
    }

    public function test_admin_cannot_open_route_without_permission(): void
    {
        $response = $this->withSession([
            'admin_logged_in' => true,
            'admin_email' => 'hr@example.com',
            'admin_permissions' => ['employees'],
        ])->get('/admin/payments');

        $response->assertForbidden();
    }
}
