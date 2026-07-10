<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'logout_reminder_sent_at')) {
                $table->timestamp('logout_reminder_sent_at')->nullable()->after('leave_admin_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'logout_reminder_sent_at')) {
                $table->dropColumn('logout_reminder_sent_at');
            }
        });
    }
};
