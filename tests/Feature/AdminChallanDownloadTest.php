<?php

namespace Tests\Feature;

use App\Models\Challan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminChallanDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_saved_challan_pdf(): void
    {
        $user = User::factory()->create();

        $challan = Challan::query()->create([
            'user_id' => $user->id,
            'challan_no' => 'CHL-9001',
            'challan_date' => '2026-05-27',
            'party_name' => 'Bapu Thambare',
            'material_machine' => 'Tractor',
            'vehicle_no' => 'MH 11 BZ 2663',
            'measurement' => '10 1W',
            'location' => 'Open to Khalapur',
            'delivery_time' => '9:00am to 13:00pm, 14:00pm to 18:00pm',
            'receiver_name' => 'Receiver',
            'driver_name' => 'Driver',
        ]);

        $pdfPath = 'challans/' . $user->id . '/challan-' . $challan->id . '-CHL-9001.pdf';
        Storage::disk('local')->put($pdfPath, '%PDF-1.4');
        $challan->update(['pdf_file_path' => $pdfPath]);

        $response = $this->withSession([
            'admin_logged_in' => true,
            'admin_email' => 'constructkaroadmin@gmail.com',
        ])->get('/admin/challans/' . $challan->id . '/download');

        $response->assertStatus(200)
            ->assertDownload('challan-' . $challan->id . '-CHL-9001.pdf');
    }
}
