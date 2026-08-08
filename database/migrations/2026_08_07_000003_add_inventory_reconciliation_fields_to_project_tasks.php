<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('project_tasks', 'material_id')) {
                $table->foreignId('material_id')->nullable()->after('material_template')->constrained('materials')->nullOnDelete();
            }

            if (! Schema::hasColumn('project_tasks', 'opening_stock_qty')) {
                $table->decimal('opening_stock_qty', 12, 3)->default(0)->after('material_id');
            }

            if (! Schema::hasColumn('project_tasks', 'receipt_qty')) {
                $table->decimal('receipt_qty', 12, 3)->default(0)->after('opening_stock_qty');
            }

            if (! Schema::hasColumn('project_tasks', 'issue_consumption_qty')) {
                $table->decimal('issue_consumption_qty', 12, 3)->default(0)->after('receipt_qty');
            }

            if (! Schema::hasColumn('project_tasks', 'return_qty')) {
                $table->decimal('return_qty', 12, 3)->default(0)->after('issue_consumption_qty');
            }

            if (! Schema::hasColumn('project_tasks', 'closing_stock_qty')) {
                $table->decimal('closing_stock_qty', 12, 3)->default(0)->after('return_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('project_tasks', 'closing_stock_qty') ? 'closing_stock_qty' : null,
                Schema::hasColumn('project_tasks', 'return_qty') ? 'return_qty' : null,
                Schema::hasColumn('project_tasks', 'issue_consumption_qty') ? 'issue_consumption_qty' : null,
                Schema::hasColumn('project_tasks', 'receipt_qty') ? 'receipt_qty' : null,
                Schema::hasColumn('project_tasks', 'opening_stock_qty') ? 'opening_stock_qty' : null,
            ]);

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }

            if (Schema::hasColumn('project_tasks', 'material_id')) {
                $table->dropConstrainedForeignId('material_id');
            }
        });
    }
};
