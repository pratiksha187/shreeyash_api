<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mir_file_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->nullable();
            $table->string('material', 200);
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 50);
            $table->string('location', 200);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('report_date', 'mir_report_date_index');
            $table->index('material', 'mir_material_index');
            $table->index('location', 'mir_location_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mir_file_reports');
    }
};
