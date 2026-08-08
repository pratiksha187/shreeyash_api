<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('safety_items')) {
            Schema::create('safety_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('category')->nullable();
                $table->string('unit', 50)->nullable();
                $table->decimal('minimum_stock', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['company_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('safety_stocks')) {
            Schema::create('safety_stocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('safety_item_id');
                $table->unsignedBigInteger('labour_site_id')->nullable();
                $table->decimal('available_quantity', 12, 2)->default(0);
                $table->timestamps();

                $table->unique(['company_id', 'safety_item_id', 'labour_site_id'], 'safety_stock_unique');
                $table->index(['company_id', 'safety_item_id']);
            });
        }

        if (! Schema::hasTable('safety_purchases')) {
            Schema::create('safety_purchases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('safety_item_id');
                $table->unsignedBigInteger('stock_labour_site_id')->nullable();
                $table->date('purchase_date')->nullable();
                $table->string('supplier_name')->nullable();
                $table->string('bill_no')->nullable();
                $table->decimal('quantity', 12, 2);
                $table->decimal('rate', 12, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'purchase_date']);
            });
        }

        if (! Schema::hasTable('safety_requests')) {
            Schema::create('safety_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('safety_item_id');
                $table->unsignedBigInteger('labour_site_id')->nullable();
                $table->unsignedBigInteger('project_id')->nullable();
                $table->unsignedBigInteger('project_task_id')->nullable();
                $table->date('request_date')->nullable();
                $table->decimal('requested_quantity', 12, 2);
                $table->decimal('approved_quantity', 12, 2)->default(0);
                $table->decimal('issued_quantity', 12, 2)->default(0);
                $table->string('requested_by')->nullable();
                $table->string('priority', 30)->default('normal');
                $table->string('status', 40)->default('pending');
                $table->text('purpose')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status']);
            });
        }

        if (! Schema::hasTable('safety_issues')) {
            Schema::create('safety_issues', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('safety_request_id')->nullable();
                $table->unsignedBigInteger('safety_item_id');
                $table->unsignedBigInteger('labour_site_id')->nullable();
                $table->unsignedBigInteger('project_id')->nullable();
                $table->unsignedBigInteger('project_task_id')->nullable();
                $table->decimal('issued_quantity', 12, 2);
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'issued_at']);
            });
        }

        if (! Schema::hasTable('safety_stock_movements')) {
            Schema::create('safety_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('safety_item_id');
                $table->unsignedBigInteger('labour_site_id')->nullable();
                $table->unsignedBigInteger('project_id')->nullable();
                $table->unsignedBigInteger('project_task_id')->nullable();
                $table->string('type', 40);
                $table->decimal('quantity', 12, 2);
                $table->decimal('balance_after', 12, 2)->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'safety_item_id', 'labour_site_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_stock_movements');
        Schema::dropIfExists('safety_issues');
        Schema::dropIfExists('safety_requests');
        Schema::dropIfExists('safety_purchases');
        Schema::dropIfExists('safety_stocks');
        Schema::dropIfExists('safety_items');
    }
};
