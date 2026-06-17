<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labour_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('labour_attendances', 'in_time')) {
                $table->time('in_time')->nullable()->after('status');
            }

            if (! Schema::hasColumn('labour_attendances', 'out_time')) {
                $table->time('out_time')->nullable()->after('in_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('labour_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('labour_attendances', 'out_time')) {
                $table->dropColumn('out_time');
            }

            if (Schema::hasColumn('labour_attendances', 'in_time')) {
                $table->dropColumn('in_time');
            }
        });
    }
};
