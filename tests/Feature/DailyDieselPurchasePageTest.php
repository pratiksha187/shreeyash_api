<?php

namespace Tests\Feature;

use App\Models\DailyDieselPurchase;
use App\Models\DailyDieselPurchaseSiteEntry;
use App\Models\LabourSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyDieselPurchasePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_daily_diesel_purchase_sheet_row(): void
    {
        $session = [
            'admin_logged_in' => true,
            'admin_email' => 'constructkaroadmin@gmail.com',
            'admin_permissions' => ['diesel_purchases'],
        ];
        $khanav = LabourSite::query()->create(['name' => 'Khanav']);
        $khalapur = LabourSite::query()->create(['name' => 'Khalapur']);
        $panvel = LabourSite::query()->create(['name' => 'Panvel']);

        $this->withSession($session)
            ->get('/admin/diesel-purchases?month=2026-05')
            ->assertOk()
            ->assertSee('May Daily Diesel Purchase')
            ->assertSee('Khanav')
            ->assertSee('Khalapur')
            ->assertSee('Panvel');

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
                        'sites' => [
                            $khanav->id => [
                                'labour_site_id' => $khanav->id,
                                'opening_balance' => '50',
                                'today_supply' => '80',
                                'used' => '80',
                            ],
                            $khalapur->id => [
                                'labour_site_id' => $khalapur->id,
                                'opening_balance' => '20',
                                'today_supply' => '50',
                                'used' => '50',
                            ],
                            $panvel->id => [
                                'labour_site_id' => $panvel->id,
                                'opening_balance' => '10',
                                'today_supply' => '25',
                                'used' => '5',
                            ],
                        ],
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
        $this->assertDatabaseHas('daily_diesel_purchase_site_entries', [
            'daily_diesel_purchase_id' => $entry->id,
            'labour_site_id' => $panvel->id,
            'opening_balance' => '10.00',
            'today_supply' => '25.00',
            'used' => '5.00',
        ]);
        $this->assertSame(3, DailyDieselPurchaseSiteEntry::query()->where('daily_diesel_purchase_id', $entry->id)->count());

        $this->withSession($session)
            ->get('/admin/diesel-purchases?month=2026-05')
            ->assertOk()
            ->assertSee('2439')
            ->assertSee('11777');
    }
}
