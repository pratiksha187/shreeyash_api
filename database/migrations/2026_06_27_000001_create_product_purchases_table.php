<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_purchases')) {
            return;
        }

        Schema::create('product_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->date('purchase_date');
            $table->string('supplier_name')->nullable();
            $table->string('invoice_no', 100)->nullable();
            $table->string('product_name');
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('transport_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'purchase_date']);
            $table->index(['company_id', 'product_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_purchases');
    }
};
