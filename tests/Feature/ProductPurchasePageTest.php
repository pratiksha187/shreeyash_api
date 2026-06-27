<?php

namespace Tests\Feature;

use App\Models\ProductPurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPurchasePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_product_purchase_entry(): void
    {
        $session = [
            'admin_logged_in' => true,
            'admin_email' => 'admin@example.com',
            'admin_role' => 'company_admin',
            'admin_permissions' => ['product_purchases'],
        ];

        $this->withSession($session)
            ->get('/admin/product-purchases?month=2026-06')
            ->assertOk()
            ->assertSee('Jun 2026 Product Purchase');

        $this->withSession($session)
            ->post('/admin/product-purchases', [
                'purchase_date' => '2026-06-26',
                'supplier_name' => 'ABC Traders',
                'invoice_no' => 'INV-25',
                'product_name' => 'Cement',
                'unit' => 'Bag',
                'quantity' => '10',
                'rate' => '350',
                'tax_amount' => '180',
                'transport_amount' => '70',
                'remarks' => 'Site material',
            ])
            ->assertRedirect('/admin/product-purchases?month=2026-06');

        $purchase = ProductPurchase::query()->first();

        $this->assertDatabaseHas('product_purchases', [
            'id' => $purchase->id,
            'purchase_date' => '2026-06-26',
            'supplier_name' => 'ABC Traders',
            'invoice_no' => 'INV-25',
            'product_name' => 'Cement',
            'total_amount' => '3750.00',
        ]);

        $this->withSession($session)
            ->get('/admin/product-purchases?month=2026-06&search=Cement')
            ->assertOk()
            ->assertSee('ABC Traders')
            ->assertSee('3750.00');

        $this->withSession($session)
            ->put('/admin/product-purchases/'.$purchase->id, [
                'purchase_date' => '2026-06-27',
                'supplier_name' => 'ABC Traders',
                'invoice_no' => 'INV-25',
                'product_name' => 'Steel',
                'unit' => 'Kg',
                'quantity' => '4',
                'rate' => '100',
                'tax_amount' => '20',
                'transport_amount' => '10',
            ])
            ->assertRedirect('/admin/product-purchases?month=2026-06');

        $this->assertDatabaseHas('product_purchases', [
            'id' => $purchase->id,
            'purchase_date' => '2026-06-27',
            'product_name' => 'Steel',
            'total_amount' => '430.00',
        ]);

        $this->withSession($session)
            ->delete('/admin/product-purchases/'.$purchase->id)
            ->assertRedirect('/admin/product-purchases?month=2026-06');

        $this->assertDatabaseMissing('product_purchases', [
            'id' => $purchase->id,
        ]);
    }
}
