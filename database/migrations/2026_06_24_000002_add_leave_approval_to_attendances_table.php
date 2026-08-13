<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'leave_approval_status')) {
                $table->string('leave_approval_status')->default('approved')->after('status');
            }

            if (! Schema::hasColumn('attendances', 'leave_approved_at')) {
                $table->dateTime('leave_approved_at')->nullable()->after('leave_approval_status');
            }

            if (! Schema::hasColumn('attendances', 'leave_admin_note')) {
                $table->text('leave_admin_note')->nullable()->after('leave_approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['leave_approval_status', 'leave_approved_at', 'leave_admin_note']);
        });
    }
};
