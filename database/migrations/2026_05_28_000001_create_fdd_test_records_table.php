<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fdd_test_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('group_number');
            $table->string('section_name', 150);
            $table->date('test_date')->nullable();
            $table->string('material', 100);
            $table->text('location');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group_number', 'sort_order'], 'fdd_group_sort_index');
            $table->index('test_date', 'fdd_test_date_index');
            $table->index('material', 'fdd_material_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fdd_test_records');
    }
};
