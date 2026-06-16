<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        foreach ($this->roles() as $id => $name) {
            DB::table('roles')->updateOrInsert(
                ['id' => $id],
                ['name' => $name]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }

    private function roles(): array
    {
        return [
            1 => 'admin',
            2 => 'HR',
            3 => 'vendor',
            4 => 'Engineer',
            5 => 'Supervisor',
            6 => 'IT',
            7 => 'Surveyor',
            8 => 'Store Incharge',
            9 => 'Accountant',
            10 => 'Planning Manager',
            11 => 'Tellicaller',
            12 => 'Billing/Estimation Engg',
            13 => 'Architect',
            14 => 'Site Coordination',
            15 => 'Safety',
            16 => 'PF',
            17 => 'Tender',
            18 => 'Project Manager',
            19 => 'Driver',
            20 => 'Electrician',
            21 => 'Quality Engg',
            22 => 'Operation Head',
            23 => 'Graphic Designer',
            24 => 'Jr. Civil Engg',
            25 => 'Site Store Superviseor',
        ];
    }
};
