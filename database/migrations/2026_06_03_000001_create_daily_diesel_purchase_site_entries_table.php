<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('daily_diesel_purchase_site_entries')) {
            return;
        }

        Schema::create('daily_diesel_purchase_site_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_diesel_purchase_id');
            $table->foreignId('labour_site_id');
            $table->decimal('opening_balance', 10, 2)->nullable();
            $table->decimal('today_supply', 10, 2)->default(0);
            $table->decimal('used', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('daily_diesel_purchase_id', 'diesel_site_purchase_fk')
                ->references('id')
                ->on('daily_diesel_purchases')
                ->cascadeOnDelete();
            $table->foreign('labour_site_id', 'diesel_site_labour_site_fk')
                ->references('id')
                ->on('labour_sites')
                ->cascadeOnDelete();
            $table->unique(['daily_diesel_purchase_id', 'labour_site_id'], 'diesel_purchase_site_unique');
        });

        $this->copyExistingFixedSiteData();
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_diesel_purchase_site_entries');
    }

    private function copyExistingFixedSiteData(): void
    {
        if (! Schema::hasTable('daily_diesel_purchases') || ! Schema::hasTable('labour_sites')) {
            return;
        }

        $siteColumns = [
            'khanav' => 'Khanav',
            'khalapur' => 'Khalapur',
        ];

        foreach ($siteColumns as $prefix => $siteName) {
            $siteId = DB::table('labour_sites')
                ->whereRaw('LOWER(name) = ?', [strtolower($siteName)])
                ->value('id');

            if (! $siteId) {
                continue;
            }

            DB::table('daily_diesel_purchases')
                ->orderBy('id')
                ->chunk(100, function ($purchases) use ($prefix, $siteId) {
                    foreach ($purchases as $purchase) {
                        DB::table('daily_diesel_purchase_site_entries')->insertOrIgnore([
                            'daily_diesel_purchase_id' => $purchase->id,
                            'labour_site_id' => $siteId,
                            'opening_balance' => $purchase->{$prefix.'_opening_balance'},
                            'today_supply' => $purchase->{$prefix.'_today_supply'} ?? 0,
                            'used' => $purchase->{$prefix.'_used'} ?? 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }
    }
};
