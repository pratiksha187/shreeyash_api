<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('units') || ! Schema::hasTable('companies')) {
            return;
        }

        $defaults = [
            ['name' => 'CUM', 'description' => 'Cubic meter'],
            ['name' => 'SQM', 'description' => 'Square meter'],
            ['name' => 'RMT', 'description' => 'Running meter'],
            ['name' => 'Nos', 'description' => 'Numbers'],
            ['name' => 'Bag', 'description' => 'Bag'],
            ['name' => 'KG', 'description' => 'Kilogram'],
            ['name' => 'MT', 'description' => 'Metric ton'],
            ['name' => 'LTR', 'description' => 'Liter'],
        ];

        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
            foreach ($defaults as $unit) {
                DB::table('units')->updateOrInsert(
                    ['company_id' => $companyId, 'name' => $unit['name']],
                    [
                        'description' => $unit['description'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('units')) {
            return;
        }

        DB::table('units')->whereIn('name', ['CUM', 'SQM', 'RMT', 'Nos', 'Bag', 'KG', 'MT', 'LTR'])->delete();
    }
};
