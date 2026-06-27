<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendances', 'leave_type')) {
            return;
        }

        DB::table('attendances')
            ->where('status', 'leave')
            ->whereNull('leave_type')
            ->update(['leave_type' => 'casual']);
    }

    public function down(): void
    {
        //
    }
};
