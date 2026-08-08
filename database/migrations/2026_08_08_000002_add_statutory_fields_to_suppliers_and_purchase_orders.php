<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (! Schema::hasColumn('suppliers', 'gst_registration_type')) {
                    $table->string('gst_registration_type', 80)->nullable()->after('gstin');
                }
                if (! Schema::hasColumn('suppliers', 'gst_return_status')) {
                    $table->string('gst_return_status', 80)->nullable()->after('gst_registration_type');
                }
                if (! Schema::hasColumn('suppliers', 'tds_section')) {
                    $table->string('tds_section', 80)->nullable()->after('gst_return_status');
                }
                if (! Schema::hasColumn('suppliers', 'tds_percent')) {
                    $table->decimal('tds_percent', 5, 2)->nullable()->after('tds_section');
                }
                if (! Schema::hasColumn('suppliers', 'e_invoice_applicable')) {
                    $table->boolean('e_invoice_applicable')->default(false)->after('tds_percent');
                }
                if (! Schema::hasColumn('suppliers', 'e_way_bill_applicable')) {
                    $table->boolean('e_way_bill_applicable')->default(false)->after('e_invoice_applicable');
                }
                if (! Schema::hasColumn('suppliers', 'vendor_reconciliation_status')) {
                    $table->string('vendor_reconciliation_status', 80)->nullable()->after('e_way_bill_applicable');
                }
                if (! Schema::hasColumn('suppliers', 'auditor_export_note')) {
                    $table->text('auditor_export_note')->nullable()->after('vendor_reconciliation_status');
                }
            });
        }

        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_orders', 'supplier_tds_section')) {
                    $table->string('supplier_tds_section', 80)->nullable()->after('supplier_ref');
                }
                if (! Schema::hasColumn('purchase_orders', 'tds_percent')) {
                    $table->decimal('tds_percent', 5, 2)->default(0)->after('supplier_tds_section');
                }
                if (! Schema::hasColumn('purchase_orders', 'tds_amount')) {
                    $table->decimal('tds_amount', 15, 2)->default(0)->after('total_amount');
                }
                if (! Schema::hasColumn('purchase_orders', 'net_payable_amount')) {
                    $table->decimal('net_payable_amount', 15, 2)->default(0)->after('tds_amount');
                }
                if (! Schema::hasColumn('purchase_orders', 'e_invoice_applicable')) {
                    $table->boolean('e_invoice_applicable')->default(false)->after('net_payable_amount');
                }
                if (! Schema::hasColumn('purchase_orders', 'e_way_bill_applicable')) {
                    $table->boolean('e_way_bill_applicable')->default(false)->after('e_invoice_applicable');
                }
                if (! Schema::hasColumn('purchase_orders', 'vendor_reconciliation_status')) {
                    $table->string('vendor_reconciliation_status', 80)->nullable()->after('e_way_bill_applicable');
                }
                if (! Schema::hasColumn('purchase_orders', 'auditor_export_note')) {
                    $table->text('auditor_export_note')->nullable()->after('vendor_reconciliation_status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                foreach ([
                    'supplier_tds_section',
                    'tds_percent',
                    'tds_amount',
                    'net_payable_amount',
                    'e_invoice_applicable',
                    'e_way_bill_applicable',
                    'vendor_reconciliation_status',
                    'auditor_export_note',
                ] as $column) {
                    if (Schema::hasColumn('purchase_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                foreach ([
                    'gst_registration_type',
                    'gst_return_status',
                    'tds_section',
                    'tds_percent',
                    'e_invoice_applicable',
                    'e_way_bill_applicable',
                    'vendor_reconciliation_status',
                    'auditor_export_note',
                ] as $column) {
                    if (Schema::hasColumn('suppliers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
