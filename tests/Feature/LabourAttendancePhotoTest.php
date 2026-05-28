<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\LabourSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LabourAttendancePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_labour_attendance_photo_is_saved_and_shown_in_admin_panel(): void
    {
        Storage::fake('public');

        $engineer = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);
        $site = LabourSite::query()->create(['name' => 'Khopoli Site']);
        $contractor = Contractor::query()->create([
            'labour_site_id' => $site->id,
            'name' => 'Test Contractor',
        ]);
        $labour = Labour::query()->create([
            'contractor_id' => $contractor->id,
            'name' => 'Test Labour',
            'trade' => 'Mason',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->post('/api/labour/attendances', [
            'labour_site_id' => $site->id,
            'contractor_id' => $contractor->id,
            'labour_id' => $labour->id,
            'attendance_date' => '2026-05-28',
            'status' => 'present',
            'work_hours' => 8,
            'photo' => UploadedFile::fake()->image('labour.jpg', 320, 320),
        ]);

        $attendance = LabourAttendance::query()->first();

        $response->assertCreated()
            ->assertJsonPath('labour_attendance.photo_url', fn ($value) => is_string($value) && str_contains($value, '/api/labour/attendances/'));

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->photo_path);
        Storage::disk('public')->assertExists($attendance->photo_path);

        $adminResponse = $this->withSession([
            'admin_logged_in' => true,
            'admin_email' => 'admin@example.com',
        ])->get('/admin/labour-attendance');

        $adminResponse->assertOk()
            ->assertSee('Labour attendance photo')
            ->assertSee('/admin/labour-attendance/' . $attendance->id . '/photo');
    }
}
