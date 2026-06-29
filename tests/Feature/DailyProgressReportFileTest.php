<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\DailyProgressReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DailyProgressReportFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_dpr_accepts_images_documents_spreadsheets_and_csv_files(): void
    {
        Storage::fake('public');

        $engineer = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);

        $this->actingAs($engineer);
        $this->withoutMiddleware(AuthenticateApiToken::class);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->post('/api/dpr', [
            'dpr_date' => '2026-06-29',
            'site_project' => 'Khopoli Site',
            'work_summary' => 'Daily progress summary',
            'hours' => [
                [
                    'hour_number' => 1,
                    'time' => '09:00',
                    'remark' => 'Morning work',
                ],
            ],
            'dpr_files' => [
                UploadedFile::fake()->image('site.jpg', 320, 320),
                UploadedFile::fake()->create('report.pdf', 12, 'application/pdf'),
                UploadedFile::fake()->create('stock.xlsx', 12, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                UploadedFile::fake()->create('note.docx', 12, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                UploadedFile::fake()->create('items.csv', 12, 'text/csv'),
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(5, 'dpr.hours.0.files')
            ->assertJsonPath('dpr.file_count', 5)
            ->assertJsonPath('dpr.hours.0.files.1.original_name', 'report.pdf')
            ->assertJsonPath('dpr.hours.0.files.1.is_image', false);

        $report = DailyProgressReport::query()->with('hours.photos')->first();

        $this->assertNotNull($report);
        $this->assertCount(5, $report->hours->first()->photos);

        foreach ($report->hours->first()->photos as $file) {
            Storage::disk('public')->assertExists($file->photo_path);
        }
    }
}
