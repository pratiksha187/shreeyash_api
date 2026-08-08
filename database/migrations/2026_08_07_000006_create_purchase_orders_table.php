<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('po_no', 80);
                $table->date('po_date');
                $table->string('supplier_name');
                $table->text('supplier_address')->nullable();
                $table->string('supplier_gstin', 40)->nullable();
                $table->string('supplier_ref', 120)->nullable();
                $table->string('other_reference', 120)->nullable();
                $table->string('dispatched_through', 120)->nullable();
                $table->string('destination', 160)->nullable();
                $table->string('consignee_name')->nullable();
                $table->text('delivery_location')->nullable();
                $table->string('consignee_gstin', 40)->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('cgst_amount', 15, 2)->default(0);
                $table->decimal('sgst_amount', 15, 2)->default(0);
                $table->decimal('igst_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->text('terms')->nullable();
                $table->string('status', 40)->default('draft');
                $table->timestamps();

                $table->unique(['company_id', 'po_no']);
                $table->index(['company_id', 'po_date']);
            });
        }

        if (! Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('item_description');
                $table->string('hsn_code', 80)->nullable();
                $table->decimal('quantity', 15, 3)->default(0);
                $table->string('unit', 40)->nullable();
                $table->decimal('rate', 15, 2)->default(0);
                $table->decimal('amount', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
