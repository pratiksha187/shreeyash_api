<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('challans', 'pdf_file_path')) {
            return;
        }

        Schema::table('challans', function (Blueprint $table) {
            $table->string('pdf_file_path')->nullable()->after('driver_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('challans', 'pdf_file_path')) {
            return;
        }

        Schema::table('challans', function (Blueprint $table) {
            $table->dropColumn('pdf_file_path');
        });
    }
};
