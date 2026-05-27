<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payments', 'pdf_file_path')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('pdf_file_path')->nullable()->after('net_payable');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payments', 'pdf_file_path')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('pdf_file_path');
        });
    }
};
