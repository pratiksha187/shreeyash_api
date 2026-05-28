<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('labour_attendances', 'photo_path')) {
            return;
        }

        Schema::table('labour_attendances', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('labour_attendances', 'photo_path')) {
            return;
        }

        Schema::table('labour_attendances', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
