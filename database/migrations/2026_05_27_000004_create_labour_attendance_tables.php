<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labour_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name', 'labour_sites_name_unique');
        });

        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_site_id');
            $table->string('name');
            $table->string('mobile', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('labour_site_id', 'contractors_site_fk')->references('id')->on('labour_sites')->cascadeOnDelete();
            $table->unique(['labour_site_id', 'name'], 'contractors_site_name_unique');
        });

        Schema::create('labours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id');
            $table->string('name');
            $table->string('mobile', 20)->nullable();
            $table->string('labour_code', 50)->nullable();
            $table->string('trade', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('contractor_id', 'labours_contractor_fk')->references('id')->on('contractors')->cascadeOnDelete();
            $table->index(['contractor_id', 'name'], 'labours_contractor_name_index');
        });

        Schema::create('labour_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engineer_user_id');
            $table->foreignId('labour_site_id');
            $table->foreignId('contractor_id');
            $table->foreignId('labour_id');
            $table->date('attendance_date');
            $table->string('status', 30)->default('present');
            $table->decimal('work_hours', 5, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('approval_status', 30)->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('engineer_user_id', 'labour_att_engineer_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('labour_site_id', 'labour_att_site_fk')->references('id')->on('labour_sites')->cascadeOnDelete();
            $table->foreign('contractor_id', 'labour_att_contractor_fk')->references('id')->on('contractors')->cascadeOnDelete();
            $table->foreign('labour_id', 'labour_att_labour_fk')->references('id')->on('labours')->cascadeOnDelete();
            $table->unique(['labour_id', 'attendance_date'], 'labour_att_labour_date_unique');
            $table->index(['approval_status', 'attendance_date'], 'labour_att_approval_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_attendances');
        Schema::dropIfExists('labours');
        Schema::dropIfExists('contractors');
        Schema::dropIfExists('labour_sites');
    }
};
