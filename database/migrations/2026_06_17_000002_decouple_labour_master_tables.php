<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contractors')) {
            $connection = Schema::getConnection();

            $this->trySchemaChange(fn () => $connection->statement('ALTER TABLE contractors DROP FOREIGN KEY contractors_site_fk'));
            $this->trySchemaChange(fn () => $connection->statement('ALTER TABLE contractors DROP INDEX contractors_site_name_unique'));

            Schema::table('contractors', function (Blueprint $table) {
                if (Schema::hasColumn('contractors', 'labour_site_id')) {
                    $table->foreignId('labour_site_id')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('labours')) {
            $connection = Schema::getConnection();

            $this->trySchemaChange(fn () => $connection->statement('ALTER TABLE labours DROP FOREIGN KEY labours_contractor_fk'));
            $this->trySchemaChange(fn () => $connection->statement('ALTER TABLE labours DROP INDEX labours_contractor_name_index'));

            Schema::table('labours', function (Blueprint $table) {
                if (Schema::hasColumn('labours', 'contractor_id')) {
                    $table->foreignId('contractor_id')->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contractors')) {
            Schema::table('contractors', function (Blueprint $table) {
                $this->trySchemaChange(fn () => $table->foreign('labour_site_id', 'contractors_site_fk')->references('id')->on('labour_sites')->cascadeOnDelete());
                $this->trySchemaChange(fn () => $table->unique(['labour_site_id', 'name'], 'contractors_site_name_unique'));
            });
        }

        if (Schema::hasTable('labours')) {
            Schema::table('labours', function (Blueprint $table) {
                $this->trySchemaChange(fn () => $table->foreign('contractor_id', 'labours_contractor_fk')->references('id')->on('contractors')->cascadeOnDelete());
                $this->trySchemaChange(fn () => $table->index(['contractor_id', 'name'], 'labours_contractor_name_index'));
            });
        }
    }

    private function trySchemaChange(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable) {
            // Some local/test databases may already be in the desired shape.
        }
    }
};
