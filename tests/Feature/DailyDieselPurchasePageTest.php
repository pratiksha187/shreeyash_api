<?php

namespace Tests\Feature;

use App\Models\DailyDieselPurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyDieselPurchasePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_daily_diesel_purchase_sheet_row(): void
    {
        $session = [
            'admin_logged_in' => true,
            'admin_email' => 'admin@example.com',
            'admin_permissions' => ['diesel_purchases'],
        ];

        $this->withSession($session)
            ->get('/admin/diesel-purchases?month=2026-05')
            ->assertOk()
            ->assertSee('May Daily Diesel Purchase')
            ->assertSee('Khanav')
            ->assertSee('Khalapur');

        $this->withSession($session)
            ->post('/admin/diesel-purchases/monthly-entries', [
                'month' => '2026-05',
                'entries' => [
                    '2026-05-02' => [
                        'entry_date' => '2026-05-02',
                        'challan_no' => '2439',
                        'campar' => '15',
                        'diesel_ltr' => '130',
                        'rate' => '90.59',
                        'khanav_opening_balance' => '50',
                        'khanav_today_supply' => '80',
                        'khanav_used' => '80',
                        'khalapur_opening_balance' => '20',
                        'khalapur_today_supply' => '50',
                        'khalapur_used' => '50',
                    ],
                ],
            ])
            ->assertRedirect('/admin/diesel-purchases?month=2026-05');

        $this->assertDatabaseHas('daily_diesel_purchases', [
            'entry_date' => '2026-05-02',
            'challan_no' => '2439',
            'campar' => '15',
        ]);

        $entry = DailyDieselPurchase::query()->whereDate('entry_date', '2026-05-02')->first();
        $this->assertSame('130.00', $entry->diesel_ltr);

        $this->withSession($session)
            ->get('/admin/diesel-purchases?month=2026-05')
            ->assertOk()
            ->assertSee('2439')
            ->assertSee('11777');
    }
}
