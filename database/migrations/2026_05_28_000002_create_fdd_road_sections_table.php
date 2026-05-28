<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fdd_road_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('group_number');
            $table->string('name', 150)->unique();
            $table->timestamps();

            $table->index('group_number', 'fdd_road_sections_group_index');
        });

        Schema::table('fdd_test_records', function (Blueprint $table) {
            $table->foreignId('fdd_road_section_id')
                ->nullable()
                ->after('id')
                ->constrained('fdd_road_sections')
                ->nullOnDelete();
        });

        DB::table('fdd_test_records')
            ->select('section_name', DB::raw('MIN(group_number) as group_number'))
            ->whereNotNull('section_name')
            ->groupBy('section_name')
            ->orderBy('group_number')
            ->orderBy('section_name')
            ->get()
            ->each(function ($section): void {
                $sectionId = DB::table('fdd_road_sections')->insertGetId([
                    'group_number' => $section->group_number,
                    'name' => $section->section_name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('fdd_test_records')
                    ->where('section_name', $section->section_name)
                    ->update([
                        'fdd_road_section_id' => $sectionId,
                        'group_number' => $section->group_number,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('fdd_test_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fdd_road_section_id');
        });

        Schema::dropIfExists('fdd_road_sections');
    }
};
