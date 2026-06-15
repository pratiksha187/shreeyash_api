<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mobile_device_id')) {
                $table->string('mobile_device_id', 64)->nullable()->after('api_token');
            }

            if (! Schema::hasColumn('users', 'mobile_device_name')) {
                $table->string('mobile_device_name')->nullable()->after('mobile_device_id');
            }

            if (! Schema::hasColumn('users', 'mobile_device_registered_at')) {
                $table->timestamp('mobile_device_registered_at')->nullable()->after('mobile_device_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mobile_device_registered_at')) {
                $table->dropColumn('mobile_device_registered_at');
            }

            if (Schema::hasColumn('users', 'mobile_device_name')) {
                $table->dropColumn('mobile_device_name');
            }

            if (Schema::hasColumn('users', 'mobile_device_id')) {
                $table->dropColumn('mobile_device_id');
            }
        });
    }
};
