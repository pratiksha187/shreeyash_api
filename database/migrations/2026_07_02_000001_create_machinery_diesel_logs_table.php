<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('machinery_diesel_logs')) {
            return;
        }

        Schema::create('machinery_diesel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('engineer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('labour_site_id')->nullable()->constrained('labour_sites')->nullOnDelete();
            $table->date('issue_date');
            $table->string('machinery');
            $table->decimal('minimum_stock_ltr', 10, 2)->default(0);
            $table->decimal('daily_diesel_for_8hr_ltr', 10, 2)->default(0);
            $table->decimal('yesterday_balance_ltr', 10, 2)->default(0);
            $table->decimal('diesel_to_issue_today_ltr', 10, 2)->default(0);
            $table->decimal('actual_diesel_issued_today_ltr', 10, 2)->default(0);
            $table->decimal('extra_diesel_issued_ltr', 10, 2)->default(0);
            $table->decimal('total_diesel_available_after_filling_ltr', 10, 2)->default(0);
            $table->decimal('hours_worked', 5, 2)->default(8);
            $table->decimal('expected_consumption_ltr', 10, 2)->default(0);
            $table->decimal('expected_closing_balance_ltr', 10, 2)->default(0);
            $table->decimal('evening_physical_balance_ltr', 10, 2)->nullable();
            $table->decimal('difference_ltr', 10, 2)->nullable();
            $table->decimal('diesel_to_issue_tomorrow_ltr', 10, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'issue_date', 'machinery'], 'machinery_diesel_company_date_machine_unique');
            $table->index(['issue_date', 'machinery'], 'machinery_diesel_date_machine_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machinery_diesel_logs');
    }
};
