<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLocationRadiusTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_is_allowed_within_fifty_meters_of_saved_location(): void
    {
        $user = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);
        Location::query()->create([
            'name' => 'Head Office',
            'latitude' => 18.7841490,
            'longitude' => 73.3398810,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->postJson('/api/attendance/clock-in', [
            'lat' => 18.7841500,
            'lng' => 73.3398820,
        ]);

        $response->assertCreated()
            ->assertJsonPath('attendance.user_id', $user->id);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'attendance_date' => today()->toDateString(),
            'status' => 'present',
        ]);
    }

    public function test_clock_in_is_rejected_outside_fifty_meters_of_saved_locations(): void
    {
        $user = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);
        Location::query()->create([
            'name' => 'Head Office',
            'latitude' => 18.7841490,
            'longitude' => 73.3398810,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->postJson('/api/attendance/clock-in', [
            'latitude' => 18.8000000,
            'longitude' => 73.3600000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('nearest_location.name', 'Head Office')
            ->assertJsonPath('allowed_radius_meters', 50);

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'attendance_date' => today()->toDateString(),
        ]);
    }

    public function test_clock_out_is_rejected_outside_fifty_meters_of_saved_locations(): void
    {
        $user = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);
        Location::query()->create([
            'name' => 'Head Office',
            'latitude' => 18.7841490,
            'longitude' => 73.3398810,
        ]);
        Attendance::query()->create([
            'user_id' => $user->id,
            'attendance_date' => today()->toDateString(),
            'status' => 'present',
            'check_in_at' => now()->subHour(),
            'latitude' => 18.7841490,
            'longitude' => 73.3398810,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->postJson('/api/attendance/clock-out', [
            'latitude' => 18.8000000,
            'longitude' => 73.3600000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('nearest_location.name', 'Head Office');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'attendance_date' => today()->toDateString(),
            'check_out_at' => null,
        ]);
    }
}
