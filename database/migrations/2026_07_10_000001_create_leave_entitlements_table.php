<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_entitlements')) {
            return;
        }

        Schema::create('leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('leave_year_start');
            $table->date('leave_year_end');
            $table->unsignedSmallInteger('casual_leave_limit')->default(4);
            $table->unsignedSmallInteger('sick_leave_limit')->default(4);
            $table->unsignedSmallInteger('paid_leave_limit')->default(4);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'leave_year_start', 'leave_year_end'], 'leave_entitlements_user_year_unique');
            $table->index(['company_id', 'leave_year_start', 'leave_year_end'], 'leave_entitlements_company_year_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_entitlements');
    }
};
