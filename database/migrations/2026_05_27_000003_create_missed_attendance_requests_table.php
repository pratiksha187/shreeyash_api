<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missed_attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->date('attendance_date');
            $table->string('request_for', 30);
            $table->text('reason');
            $table->string('status', 30)->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'missed_attendance_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['status', 'attendance_date'], 'missed_attendance_status_date_index');
            $table->index(['user_id', 'attendance_date'], 'missed_attendance_user_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missed_attendance_requests');
    }
};
