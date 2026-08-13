<?php

use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    public function up(): void
    {
        // Attendance is already written in the company local timezone by the app.
        // This migration used to shift existing rows by +5:30, which made actual
        // 09:16 punches appear as 14:46. Keep it as a no-op for fresh tenants.
    }

    public function down(): void
    {
    }
};
