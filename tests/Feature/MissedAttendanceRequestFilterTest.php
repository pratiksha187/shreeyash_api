<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MissedAttendanceRequestController;
use App\Models\MissedAttendanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class MissedAttendanceRequestFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_missed_request_filters_limit_the_results_and_summary(): void
    {
        View::addNamespace('admin', resource_path('views/admin'));

        $matchedEmployee = User::factory()->create([
            'name' => 'Matched Employee',
            'role' => 'employee',
        ]);
        $otherEmployee = User::factory()->create([
            'name' => 'Other Employee',
            'role' => 'employee',
        ]);

        MissedAttendanceRequest::query()->create([
            'user_id' => $matchedEmployee->id,
            'attendance_date' => '2026-08-01',
            'request_for' => 'clock_in',
            'reason' => 'Forgot to clock in',
            'status' => 'pending',
        ]);
        MissedAttendanceRequest::query()->create([
            'user_id' => $matchedEmployee->id,
            'attendance_date' => '2026-07-31',
            'request_for' => 'clock_in',
            'reason' => 'Old request',
            'status' => 'pending',
        ]);
        MissedAttendanceRequest::query()->create([
            'user_id' => $otherEmployee->id,
            'attendance_date' => '2026-08-01',
            'request_for' => 'clock_in',
            'reason' => 'Other employee request',
            'status' => 'pending',
        ]);
        MissedAttendanceRequest::query()->create([
            'user_id' => $matchedEmployee->id,
            'attendance_date' => '2026-08-01',
            'request_for' => 'clock_out',
            'reason' => 'Rejected request',
            'status' => 'rejected',
        ]);

        $request = Request::create('/admin/missed-requests', 'GET', [
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-01',
            'user_id' => (string) $matchedEmployee->id,
            'request_for' => 'clock_in',
            'status' => 'pending',
        ]);

        $view = app(MissedAttendanceRequestController::class)->index($request);
        $data = $view->getData();

        $this->assertCount(1, $data['requests']->items());
        $this->assertSame($matchedEmployee->id, $data['requests']->items()[0]->user_id);
        $this->assertSame('clock_in', $data['requests']->items()[0]->request_for);
        $this->assertSame('pending', $data['requests']->items()[0]->status);
        $this->assertSame([
            'total' => 1,
            'pending' => 1,
            'approved' => 0,
            'rejected' => 0,
        ], $data['summary']);
    }
}
