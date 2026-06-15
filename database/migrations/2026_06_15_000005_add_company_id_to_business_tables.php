<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'attendances',
        'payments',
        'complaints',
        'missed_attendance_requests',
        'daily_progress_reports',
        'challans',
        'labour_sites',
        'contractors',
        'labours',
        'labour_attendances',
        'locations',
        'vehicles',
        'vehicle_logs',
        'daily_diesel_purchases',
        'daily_diesel_purchase_site_entries',
        'fdd_road_sections',
        'fdd_test_records',
        'mir_file_reports',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
