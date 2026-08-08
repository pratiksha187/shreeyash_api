<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('labours')) {
            return;
        }

        Schema::table('labours', function (Blueprint $table) {
            if (! Schema::hasColumn('labours', 'labour_type')) {
                $table->string('labour_type', 40)->default('daily_wage')->after('trade');
            }
            if (! Schema::hasColumn('labours', 'work_category')) {
                $table->string('work_category', 120)->nullable()->after('labour_type');
            }
            if (! Schema::hasColumn('labours', 'daily_wage_rate')) {
                $table->decimal('daily_wage_rate', 12, 2)->default(0)->after('work_category');
            }
            if (! Schema::hasColumn('labours', 'overtime_rate')) {
                $table->decimal('overtime_rate', 12, 2)->default(0)->after('daily_wage_rate');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('labours')) {
            return;
        }

        Schema::table('labours', function (Blueprint $table) {
            foreach (['overtime_rate', 'daily_wage_rate', 'work_category', 'labour_type'] as $column) {
                if (Schema::hasColumn('labours', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
