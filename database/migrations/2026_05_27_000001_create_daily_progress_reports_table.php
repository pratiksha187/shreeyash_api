<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_progress_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->date('dpr_date');
            $table->string('site_project');
            $table->text('work_summary');
            $table->timestamps();

            $table->foreign('user_id', 'dpr_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'dpr_date'], 'dpr_user_date_unique');
            $table->index('dpr_date', 'dpr_date_index');
        });

        Schema::create('daily_progress_report_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_progress_report_id');
            $table->unsignedInteger('hour_number');
            $table->time('work_time');
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->foreign('daily_progress_report_id', 'dpr_hours_report_fk')
                ->references('id')
                ->on('daily_progress_reports')
                ->cascadeOnDelete();
            $table->unique(['daily_progress_report_id', 'hour_number'], 'dpr_hours_report_hour_unique');
        });

        Schema::create('daily_progress_report_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_progress_report_hour_id');
            $table->string('photo_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->foreign('daily_progress_report_hour_id', 'dpr_photos_hour_fk')
                ->references('id')
                ->on('daily_progress_report_hours')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_progress_report_photos');
        Schema::dropIfExists('daily_progress_report_hours');
        Schema::dropIfExists('daily_progress_reports');
    }
};
