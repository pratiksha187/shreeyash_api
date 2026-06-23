<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_progress_reports')) {
            return;
        }

        Schema::table('daily_progress_reports', function (Blueprint $table) {
            $this->trySchemaChange(fn () => $table->dropUnique('dpr_user_date_unique'));
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('daily_progress_reports')) {
            return;
        }

        Schema::table('daily_progress_reports', function (Blueprint $table) {
            $this->trySchemaChange(fn () => $table->unique(['user_id', 'dpr_date'], 'dpr_user_date_unique'));
        });
    }

    private function trySchemaChange(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable) {
            // Some tenant databases may already have the desired index state.
        }
    }
};
