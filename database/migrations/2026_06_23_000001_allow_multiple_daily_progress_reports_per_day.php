<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_progress_reports')) {
            return;
        }

        Schema::table('daily_progress_reports', function (Blueprint $table) {
            $this->trySchemaChange(fn () => $table->dropForeign('dpr_user_fk'));
        });

        $this->trySchemaChange(fn () => DB::statement('ALTER TABLE daily_progress_reports DROP INDEX IF EXISTS dpr_user_date_unique'));

        Schema::table('daily_progress_reports', function (Blueprint $table) {
            $this->trySchemaChange(fn () => $table->foreign('user_id', 'dpr_user_fk')->references('id')->on('users')->cascadeOnDelete());
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('daily_progress_reports')) {
            return;
        }

        $this->trySchemaChange(fn () => DB::statement('ALTER TABLE daily_progress_reports DROP INDEX IF EXISTS dpr_user_date_unique'));

        Schema::table('daily_progress_reports', function (Blueprint $table) {
            $this->trySchemaChange(fn () => $table->unique(['user_id', 'dpr_date'], 'dpr_user_date_unique'));
        });

        Schema::table('daily_progress_reports', function (Blueprint $table) {
            $this->trySchemaChange(fn () => $table->foreign('user_id', 'dpr_user_fk')->references('id')->on('users')->cascadeOnDelete());
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
