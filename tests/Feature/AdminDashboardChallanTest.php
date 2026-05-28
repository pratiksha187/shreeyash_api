<?php

namespace Tests\Feature;

use App\Models\Challan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardChallanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_recent_challans(): void
    {
        $user = User::factory()->create([
            'name' => 'Asha Sharma',
            'mobile' => '9999999999',
        ]);

        Challan::query()->create([
            'user_id' => $user->id,
            'challan_no' => 'CHL-1001',
            'challan_date' => '2026-05-26',
            'party_name' => 'ABC Construction',
            'material_machine' => 'Mixer',
            'vehicle_no' => 'MH12AB1234',
            'measurement' => '10 MT',
            'location' => 'Site A',
            'delivery_time' => '11:30 AM',
            'receiver_name' => 'Rohan',
            'driver_name' => 'Mahesh',
        ]);

        $response = $this->withSession([
            'admin_logged_in' => true,
            'admin_email' => 'admin@example.com',
        ])->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Recent Challans');
        $response->assertSee('CHL-1001');
        $response->assertSee('ABC Construction');
        $response->assertSee('Asha Sharma');
    }
}
