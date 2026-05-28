<?php

namespace Tests\Feature;

use App\Models\Challan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChallanPdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_challan_generates_and_stores_pdf(): void
    {
        $user = User::factory()->create([
            'api_token' => hash('sha256', 'mobile-token'),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->postJson('/api/challans', [
            'challan_no' => 'CHL-9001',
            'challan_date' => '27/05/2026',
            'party_name' => 'Bapu Thambare',
            'material_machine' => 'Tractor',
            'vehicle_no' => 'MH 11 BZ 2663',
            'measurement' => '10 1W',
            'location' => 'Open to Khalapur',
            'delivery_time' => '9:00am to 13:00pm, 14:00pm to 18:00pm',
            'receiver_name' => 'Receiver',
            'driver_name' => 'Driver',
        ]);

        $challan = Challan::query()->where('challan_no', 'CHL-9001')->first();

        $this->assertNotNull($challan);
        $this->assertDatabaseHas('challans', [
            'id' => $challan->id,
            'challan_no' => 'CHL-9001',
            'pdf_file_path' => 'challans/' . $challan->user_id . '/challan-' . $challan->id . '-CHL-9001.pdf',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('challan.pdf_file_path', fn ($value) => is_string($value) && str_contains($value, 'challans/'))
            ->assertJsonPath('challan.pdf_url', fn ($value) => is_string($value) && str_contains($value, '/api/challans/'));

        $challan = $user->challans()->latest()->first();

        $this->assertNotNull($challan);
        $this->assertNotNull($challan->pdf_file_path);
        $this->assertTrue(Storage::disk('local')->exists($challan->pdf_file_path));

        $pdfResponse = $this->withHeaders([
            'Authorization' => 'Bearer mobile-token',
        ])->get('/api/challans/' . $challan->id . '/pdf');

        $pdfResponse->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
