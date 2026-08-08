<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_workflows')) {
            return;
        }

        Schema::create('purchase_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requisition_no', 80)->nullable();
            $table->date('requisition_date')->nullable();
            $table->string('indent_no', 80)->nullable();
            $table->string('material_name');
            $table->string('unit', 40)->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->string('vendor_enquiry_no', 80)->nullable();
            $table->text('vendor_names')->nullable();
            $table->text('quotation_summary')->nullable();
            $table->string('selected_vendor')->nullable();
            $table->decimal('quoted_amount', 15, 2)->default(0);
            $table->decimal('approval_limit', 15, 2)->default(0);
            $table->string('approval_status', 40)->default('pending');
            $table->string('po_no', 80)->nullable();
            $table->date('po_date')->nullable();
            $table->string('po_status', 40)->default('draft');
            $table->string('grn_no', 80)->nullable();
            $table->date('grn_date')->nullable();
            $table->string('grn_status', 40)->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'requisition_date']);
            $table->index(['company_id', 'approval_status']);
            $table->index(['company_id', 'po_status']);
            $table->index(['company_id', 'grn_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_workflows');
    }
};
