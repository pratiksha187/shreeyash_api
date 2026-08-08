<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contractors')) {
            return;
        }

        Schema::table('contractors', function (Blueprint $table) {
            if (! Schema::hasColumn('contractors', 'agreement_no')) {
                $table->string('agreement_no')->nullable()->after('mobile');
            }
            if (! Schema::hasColumn('contractors', 'contract_no')) {
                $table->string('contract_no')->nullable()->after('agreement_no');
            }
            if (! Schema::hasColumn('contractors', 'work_order_no')) {
                $table->string('work_order_no')->nullable()->after('contract_no');
            }
            if (! Schema::hasColumn('contractors', 'contract_start_date')) {
                $table->date('contract_start_date')->nullable()->after('work_order_no');
            }
            if (! Schema::hasColumn('contractors', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('contract_start_date');
            }
            if (! Schema::hasColumn('contractors', 'contract_value')) {
                $table->decimal('contract_value', 14, 2)->nullable()->after('contract_end_date');
            }
            if (! Schema::hasColumn('contractors', 'progress_percent')) {
                $table->decimal('progress_percent', 5, 2)->nullable()->after('contract_value');
            }
            if (! Schema::hasColumn('contractors', 'last_measurement_date')) {
                $table->date('last_measurement_date')->nullable()->after('progress_percent');
            }
            if (! Schema::hasColumn('contractors', 'last_measurement_summary')) {
                $table->text('last_measurement_summary')->nullable()->after('last_measurement_date');
            }
            if (! Schema::hasColumn('contractors', 'last_ra_bill_no')) {
                $table->string('last_ra_bill_no')->nullable()->after('last_measurement_summary');
            }
            if (! Schema::hasColumn('contractors', 'last_ra_bill_amount')) {
                $table->decimal('last_ra_bill_amount', 14, 2)->nullable()->after('last_ra_bill_no');
            }
            if (! Schema::hasColumn('contractors', 'retention_percent')) {
                $table->decimal('retention_percent', 5, 2)->nullable()->after('last_ra_bill_amount');
            }
            if (! Schema::hasColumn('contractors', 'recovery_amount')) {
                $table->decimal('recovery_amount', 14, 2)->nullable()->after('retention_percent');
            }
            if (! Schema::hasColumn('contractors', 'tds_percent')) {
                $table->decimal('tds_percent', 5, 2)->nullable()->after('recovery_amount');
            }
            if (! Schema::hasColumn('contractors', 'gst_percent')) {
                $table->decimal('gst_percent', 5, 2)->nullable()->after('tds_percent');
            }
            if (! Schema::hasColumn('contractors', 'net_payable_amount')) {
                $table->decimal('net_payable_amount', 14, 2)->nullable()->after('gst_percent');
            }
            if (! Schema::hasColumn('contractors', 'renewal_due_date')) {
                $table->date('renewal_due_date')->nullable()->after('net_payable_amount');
            }
            if (! Schema::hasColumn('contractors', 'remarks')) {
                $table->text('remarks')->nullable()->after('renewal_due_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('contractors')) {
            return;
        }

        Schema::table('contractors', function (Blueprint $table) {
            $columns = [
                'agreement_no',
                'contract_no',
                'work_order_no',
                'contract_start_date',
                'contract_end_date',
                'contract_value',
                'progress_percent',
                'last_measurement_date',
                'last_measurement_summary',
                'last_ra_bill_no',
                'last_ra_bill_amount',
                'retention_percent',
                'recovery_amount',
                'tds_percent',
                'gst_percent',
                'net_payable_amount',
                'renewal_due_date',
                'remarks',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('contractors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
