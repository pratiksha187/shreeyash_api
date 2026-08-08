<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            $addSupplierColumn = function (string $column, callable $definition): void {
                if (! Schema::hasColumn('suppliers', $column)) {
                    Schema::table('suppliers', $definition);
                }
            };

            $addSupplierColumn('gst_registration_type', function (Blueprint $table) {
                $table->string('gst_registration_type', 80)->nullable()->after('gstin');
            });
            $addSupplierColumn('gst_return_status', function (Blueprint $table) {
                $table->string('gst_return_status', 80)->nullable()->after('gst_registration_type');
            });
            $addSupplierColumn('tds_section', function (Blueprint $table) {
                $table->string('tds_section', 80)->nullable()->after('gst_return_status');
            });
            $addSupplierColumn('tds_percent', function (Blueprint $table) {
                $table->decimal('tds_percent', 5, 2)->nullable()->after('tds_section');
            });
            $addSupplierColumn('e_invoice_applicable', function (Blueprint $table) {
                $table->boolean('e_invoice_applicable')->default(false)->after('tds_percent');
            });
            $addSupplierColumn('e_way_bill_applicable', function (Blueprint $table) {
                $table->boolean('e_way_bill_applicable')->default(false)->after('e_invoice_applicable');
            });
            $addSupplierColumn('vendor_reconciliation_status', function (Blueprint $table) {
                $table->string('vendor_reconciliation_status', 80)->nullable()->after('e_way_bill_applicable');
            });
            $addSupplierColumn('auditor_export_note', function (Blueprint $table) {
                $table->text('auditor_export_note')->nullable()->after('vendor_reconciliation_status');
            });
        }

        if (Schema::hasTable('purchase_orders')) {
            $addPurchaseOrderColumn = function (string $column, callable $definition): void {
                if (! Schema::hasColumn('purchase_orders', $column)) {
                    Schema::table('purchase_orders', $definition);
                }
            };

            $addPurchaseOrderColumn('supplier_tds_section', function (Blueprint $table) {
                $table->string('supplier_tds_section', 80)->nullable()->after('supplier_ref');
            });
            $addPurchaseOrderColumn('tds_percent', function (Blueprint $table) {
                $table->decimal('tds_percent', 5, 2)->default(0)->after('supplier_tds_section');
            });
            $addPurchaseOrderColumn('tds_amount', function (Blueprint $table) {
                $table->decimal('tds_amount', 15, 2)->default(0)->after('total_amount');
            });
            $addPurchaseOrderColumn('net_payable_amount', function (Blueprint $table) {
                $table->decimal('net_payable_amount', 15, 2)->default(0)->after('tds_amount');
            });
            $addPurchaseOrderColumn('e_invoice_applicable', function (Blueprint $table) {
                $table->boolean('e_invoice_applicable')->default(false)->after('net_payable_amount');
            });
            $addPurchaseOrderColumn('e_way_bill_applicable', function (Blueprint $table) {
                $table->boolean('e_way_bill_applicable')->default(false)->after('e_invoice_applicable');
            });
            $addPurchaseOrderColumn('vendor_reconciliation_status', function (Blueprint $table) {
                $table->string('vendor_reconciliation_status', 80)->nullable()->after('e_way_bill_applicable');
            });
            $addPurchaseOrderColumn('auditor_export_note', function (Blueprint $table) {
                $table->text('auditor_export_note')->nullable()->after('vendor_reconciliation_status');
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
