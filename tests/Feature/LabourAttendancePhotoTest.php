<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\LabourSite;
use App\Models\User;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureAdminLoggedIn;
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
        $this->actingAs($engineer);
        $this->withoutMiddleware(AuthenticateApiToken::class);
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

        $this->withoutMiddleware(EnsureAdminLoggedIn::class);

        $adminResponse = $this->withSession([
            'admin_logged_in' => true,
            'admin_email' => 'admin@example.com',
        ])->get('/admin/labour-attendance?from_date=2026-05-01&to_date=2026-05-31');

        $adminResponse->assertOk()
            ->assertSee('Labour attendance photo')
            ->assertSee('/admin/labour-attendance/' . $attendance->id . '/photo');
    }

    public function test_labour_attendance_base64_photo_is_saved(): void
    {
        Storage::fake('public');

        $engineer = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);
        $this->actingAs($engineer);
        $this->withoutMiddleware(AuthenticateApiToken::class);
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

        $onePixelPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->postJson('/api/labour/attendances', [
            'labour_site_id' => $site->id,
            'contractor_id' => $contractor->id,
            'labour_id' => $labour->id,
            'attendance_date' => '2026-05-28',
            'status' => 'present',
            'work_hours' => 8,
            'photo' => $onePixelPng,
        ]);

        $attendance = LabourAttendance::query()->first();

        $response->assertCreated()
            ->assertJsonPath('labour_attendance.photo_url', fn ($value) => is_string($value) && str_contains($value, '/api/labour/attendances/'));

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->photo_path);
        Storage::disk('public')->assertExists($attendance->photo_path);
    }

    public function test_labour_attendance_accepts_multiple_labours_and_calculates_hours_from_time(): void
    {
        $engineer = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);
        $this->actingAs($engineer);
        $this->withoutMiddleware(AuthenticateApiToken::class);
        $site = LabourSite::query()->create(['name' => 'Khopoli Site']);
        $contractor = Contractor::query()->create([
            'labour_site_id' => $site->id,
            'name' => 'Test Contractor',
        ]);
        $firstLabour = Labour::query()->create([
            'contractor_id' => $contractor->id,
            'name' => 'First Labour',
            'trade' => 'Mason',
        ]);
        $secondLabour = Labour::query()->create([
            'contractor_id' => $contractor->id,
            'name' => 'Second Labour',
            'trade' => 'Helper',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->postJson('/api/labour/attendances', [
            'labour_site_id' => $site->id,
            'contractor_id' => $contractor->id,
            'labour_ids' => [$firstLabour->id, $secondLabour->id],
            'attendance_date' => '2026-05-28',
            'status' => 'present',
            'in_time' => '09:00',
            'out_time' => '17:30',
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'labour_attendances')
            ->assertJsonPath('labour_attendances.0.work_hours', '8.50')
            ->assertJsonPath('labour_attendances.0.in_time', '09:00')
            ->assertJsonPath('labour_attendances.0.out_time', '17:30');

        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $firstLabour->id,
            'in_time' => '09:00',
            'out_time' => '17:30',
            'work_hours' => 8.50,
        ]);
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $secondLabour->id,
            'in_time' => '09:00',
            'out_time' => '17:30',
            'work_hours' => 8.50,
        ]);
    }

    public function test_labour_attendance_accepts_dot_separated_time_from_mobile_form(): void
    {
        $engineer = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);
        $this->actingAs($engineer);
        $this->withoutMiddleware(AuthenticateApiToken::class);

        $site = LabourSite::query()->create(['name' => 'Khanav']);
        $contractor = Contractor::query()->create([
            'name' => 'Irfan Ali',
            'mobile' => '8546916086',
        ]);
        $labour = Labour::query()->create([
            'name' => 'Babulal Munda',
            'labour_code' => 'BM01',
            'trade' => 'Carpenter',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->postJson('/api/labour/attendances', [
            'labour_site_id' => $site->id,
            'contractor_id' => $contractor->id,
            'labour_id' => $labour->id,
            'attendance_date' => '2026-06-17',
            'status' => 'present',
            'in_time' => '09.00',
            'out_time' => '18.00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('labour_attendance.in_time', '09:00')
            ->assertJsonPath('labour_attendance.out_time', '18:00')
            ->assertJsonPath('labour_attendance.work_hours', '9.00');

        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $labour->id,
            'in_time' => '09:00',
            'out_time' => '18:00',
            'work_hours' => 9.00,
        ]);
    }

    public function test_labour_attendance_accepts_mobile_label_status_and_selected_labour_objects(): void
    {
        $engineer = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);
        $this->actingAs($engineer);
        $this->withoutMiddleware(AuthenticateApiToken::class);

        $site = LabourSite::query()->create(['name' => 'Khanav']);
        $contractor = Contractor::query()->create([
            'labour_site_id' => $site->id,
            'name' => 'Irfan Ali',
        ]);
        $firstLabour = Labour::query()->create([
            'contractor_id' => $contractor->id,
            'name' => 'Nitesh Kumar',
            'trade' => 'Carpenter',
        ]);
        $secondLabour = Labour::query()->create([
            'contractor_id' => $contractor->id,
            'name' => 'Munjiram',
            'trade' => 'Qledpet',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->postJson('/api/labour/attendances', [
            'site_id' => $site->id,
            'contractor_id' => $contractor->id,
            'selectedLabours' => [
                ['id' => $firstLabour->id, 'name' => 'Nitesh Kumar'],
                ['id' => $secondLabour->id, 'name' => 'Munjiram'],
            ],
            'date' => '2026-07-10',
            'status' => 'Present',
            'inTime' => '09:00',
            'outTime' => '18:00',
            'workHours' => '9',
            'note' => '10+1',
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'labour_attendances')
            ->assertJsonPath('labour_attendances.0.status', 'present')
            ->assertJsonPath('labour_attendances.0.remarks', '10+1')
            ->assertJsonPath('labour_attendances.0.work_hours', '9.00');

        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $firstLabour->id,
            'status' => 'present',
            'in_time' => '09:00',
            'out_time' => '18:00',
            'work_hours' => 9.00,
            'remarks' => '10+1',
        ]);
    }

    public function test_labour_attendance_accepts_legacy_leber_endpoint_and_photo_key(): void
    {
        Storage::fake('public');

        $engineer = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);
        $this->actingAs($engineer);
        $this->withoutMiddleware(AuthenticateApiToken::class);

        $site = LabourSite::query()->create(['name' => 'Khanav']);
        $contractor = Contractor::query()->create([
            'labour_site_id' => $site->id,
            'name' => 'Irfan Ali',
        ]);
        $labour = Labour::query()->create([
            'contractor_id' => $contractor->id,
            'name' => 'Nitesh Kumar',
            'trade' => 'Carpenter',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->post('/api/leber/attendances', [
            'leberSiteId' => $site->id,
            'contractorId' => $contractor->id,
            'leberId' => $labour->id,
            'date' => '2026-08-10',
            'status' => 'Present',
            'inTime' => '09:00',
            'note' => 'Test',
            'leber_image' => UploadedFile::fake()->image('labour.jpg', 320, 320),
        ]);

        $attendance = LabourAttendance::query()->first();

        $response->assertCreated()
            ->assertJsonPath('labour_attendance.remarks', 'Test')
            ->assertJsonPath('labour_attendance.photo_url', fn ($value) => is_string($value) && str_contains($value, '/api/'));

        $this->assertNotNull($attendance);
        $this->assertSame($labour->id, $attendance->labour_id);
        Storage::disk('public')->assertExists($attendance->photo_path);
    }
}
