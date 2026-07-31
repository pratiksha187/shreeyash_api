<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Attendance;
use App\Services\MonthlyAttendanceReportMailer;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:send-monthly-emails {--month=} {--company-id=}', function (MonthlyAttendanceReportMailer $mailer) {
    $result = $mailer->sendForMonth(
        $this->option('month'),
        $this->option('company-id') ? (int) $this->option('company-id') : null
    );

    $this->info("Monthly attendance email month: {$result['month']}");
    $this->info("Companies: {$result['companies']}");
    $this->info("Sent: {$result['sent']}");
    $this->info("Skipped: {$result['skipped']}");
    $this->info("Failed: {$result['failed']}");
})->purpose('Send monthly attendance reports to employees');

Schedule::command('attendance:send-monthly-emails')
    ->dailyAt('23:55')
    ->timezone(Attendance::LOCAL_TIMEZONE)
    ->when(fn () => now(Attendance::LOCAL_TIMEZONE)->isLastOfMonth());
