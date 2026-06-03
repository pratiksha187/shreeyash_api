<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use App\Models\VehicleLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleHireBillingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_vehicle_hire_sheet_row_and_see_calculated_amount(): void
    {
        $session = [
            'admin_logged_in' => true,
            'admin_email' => 'admin@example.com',
            'admin_permissions' => ['vehicles'],
        ];

        $vehicle = Vehicle::query()->create([
            'vehicle_number' => 'MH 46 DF 2445',
            'vehicle_type' => 'JCB',
            'owner_name' => 'Shaiker Bailmare',
            'driver_name' => 'Operator',
            'driver_mobile' => '9999999999',
            'default_site' => 'Cluster 4',
            'hire_per_day_rate' => 3226,
            'hire_per_hour_rate' => 417,
            'gst_percentage' => 18,
            'tds_percentage' => 2,
        ]);

        $this->withSession($session)
            ->post("/admin/vehicles/{$vehicle->id}/monthly-entries", [
                'month' => '2026-04',
                'entries' => [
                    '2026-04-16' => [
                        'entry_date' => '2026-04-16',
                        'challan_no' => '7220',
                        'site_name' => 'Cluster 4',
                        'diesel_added' => '20',
                        'in_time' => '09:00',
                        'out_time' => '17:00',
                        'remarks' => 'JCB full day',
                    ],
                ],
            ])
            ->assertRedirect("/admin/vehicles/{$vehicle->id}?month=2026-04");

        $this->assertDatabaseHas('vehicle_logs', [
            'vehicle_id' => $vehicle->id,
            'entry_date' => '2026-04-16',
            'challan_no' => '7220',
            'site_name' => 'Cluster 4',
        ]);

        $this->assertSame(1, VehicleLog::query()->count());

        $this->withSession($session)
            ->get("/admin/vehicles/{$vehicle->id}?month=2026-04")
            ->assertOk()
            ->assertSee('Cluster 4')
            ->assertSee('417.00')
            ->assertSee('3,336.00')
            ->assertSee('3,936.48');
    }
}
