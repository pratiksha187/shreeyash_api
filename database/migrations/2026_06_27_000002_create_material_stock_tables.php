<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materials')) {
            Schema::create('materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('material_type')->nullable();
                $table->string('unit', 50)->nullable();
                $table->decimal('minimum_stock', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['company_id', 'name']);
                $table->index(['company_id', 'material_type']);
            });
        }

        if (! Schema::hasTable('material_stocks')) {
            Schema::create('material_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->foreignId('labour_site_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('available_quantity', 12, 2)->default(0);
                $table->timestamps();

                $table->unique(['company_id', 'material_id', 'labour_site_id'], 'material_stock_unique');
            });
        }

        if (! Schema::hasTable('material_requests')) {
            Schema::create('material_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('labour_site_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
                $table->date('request_date')->nullable();
                $table->date('required_by')->nullable();
                $table->string('site_project')->nullable();
                $table->string('material_name')->nullable();
                $table->string('unit', 50)->nullable();
                $table->decimal('requested_quantity', 12, 2);
                $table->decimal('approved_quantity', 12, 2)->default(0);
                $table->decimal('issued_quantity', 12, 2)->default(0);
                $table->date('required_date')->nullable();
                $table->string('priority', 30)->default('normal');
                $table->text('purpose')->nullable();
                $table->string('status', 40)->default('pending');
                $table->text('admin_note')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'required_date']);
            });
        }

        if (! Schema::hasTable('material_issues')) {
            Schema::create('material_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('material_request_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->foreignId('labour_site_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('issued_quantity', 12, 2);
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['company_id', 'issued_at']);
            });
        }

        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('material_id')->constrained()->cascadeOnDelete();
                $table->foreignId('labour_site_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 40);
                $table->decimal('quantity', 12, 2);
                $table->decimal('balance_after', 12, 2)->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'material_id', 'labour_site_id']);
                $table->index(['reference_type', 'reference_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('material_requests');
        Schema::dropIfExists('material_stocks');
        Schema::dropIfExists('materials');
    }
};
