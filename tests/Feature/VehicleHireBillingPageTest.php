<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAdminLoggedIn;
use App\Models\Company;
use App\Models\Vehicle;
use App\Models\VehicleLog;
use App\Support\Tenant;
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
            'admin_role' => 'company_admin',
            'admin_permissions' => ['vehicles'],
        ];
        $company = Company::query()->create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'status' => 'active',
        ]);
        app(Tenant::class)->set($company);
        $this->withoutMiddleware(EnsureAdminLoggedIn::class);

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
                        'first_half_in' => '09:00',
                        'first_half_out' => '17:00',
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

    public function test_vehicle_calendar_uses_custom_billing_cycle_start_day(): void
    {
        $session = [
            'admin_logged_in' => true,
            'admin_email' => 'admin@example.com',
            'admin_role' => 'company_admin',
            'admin_permissions' => ['vehicles'],
        ];
        $company = Company::query()->create([
            'name' => 'Cycle Company',
            'slug' => 'cycle-company',
            'status' => 'active',
        ]);
        app(Tenant::class)->set($company);
        $this->withoutMiddleware(EnsureAdminLoggedIn::class);

        $vehicle = Vehicle::query()->create([
            'vehicle_number' => 'MH 12 CY 1514',
            'vehicle_type' => 'Camper',
            'owner_name' => 'Cycle Owner',
            'driver_name' => 'Driver',
            'driver_mobile' => '9999999999',
            'billing_cycle_start_day' => 15,
        ]);

        foreach ([
            ['2026-07-14', 'BEFORE-CYCLE'],
            ['2026-07-15', 'START-CYCLE'],
            ['2026-08-14', 'END-CYCLE'],
            ['2026-08-15', 'AFTER-CYCLE'],
        ] as [$date, $challanNo]) {
            VehicleLog::query()->create([
                'vehicle_id' => $vehicle->id,
                'entry_date' => $date,
                'vehicle_number' => $vehicle->vehicle_number,
                'vehicle_type' => $vehicle->vehicle_type,
                'driver_name' => $vehicle->driver_name,
                'driver_mobile' => $vehicle->driver_mobile,
                'challan_no' => $challanNo,
                'in_at' => $date.' 09:00:00',
                'out_at' => $date.' 17:00:00',
            ]);
        }

        $this->withSession($session)
            ->get("/admin/vehicles/{$vehicle->id}?month=2026-07")
            ->assertOk()
            ->assertSee('15 Jul 2026 to 14 Aug 2026')
            ->assertSee('15/07/2026')
            ->assertSee('14/08/2026')
            ->assertSee('START-CYCLE')
            ->assertSee('END-CYCLE')
            ->assertDontSee('14/07/2026')
            ->assertDontSee('15/08/2026')
            ->assertDontSee('BEFORE-CYCLE')
            ->assertDontSee('AFTER-CYCLE');
    }
}
