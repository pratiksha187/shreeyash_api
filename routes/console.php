<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use App\Models\Attendance;
use App\Models\Company;
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

Artisan::command('tenants:migrate {database?} {--company-id=} {--all}', function () {
    $databases = collect();

    if ($this->option('all')) {
        $databases = Company::query()
            ->whereNotNull('database_name')
            ->pluck('database_name');
    } elseif ($this->option('company-id')) {
        $database = Company::query()
            ->whereKey($this->option('company-id'))
            ->value('database_name');

        if (! $database) {
            $this->error('Company database was not found.');

            return self::FAILURE;
        }

        $databases = collect([$database]);
    } elseif ($this->argument('database')) {
        $databases = collect([$this->argument('database')]);
    } else {
        $this->error('Pass a tenant database name, --company-id=ID, or --all.');

        return self::FAILURE;
    }

    foreach ($databases as $database) {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
            $this->error("Invalid tenant database name: {$database}");

            return self::FAILURE;
        }

        $baseConnection = config('database.default') === 'sqlite' ? 'mysql' : config('database.default');
        $config = config("database.connections.{$baseConnection}");
        $config['database'] = $database;

        Config::set('database.connections.tenant', $config);
        DB::purge('tenant');

        $this->info("Migrating tenant database: {$database}");

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--force' => true,
        ]);

        $this->line(Artisan::output());
    }

    DB::purge('tenant');

    return self::SUCCESS;
})->purpose('Run pending migrations for tenant database(s)');

Schedule::command('attendance:send-monthly-emails')
    ->dailyAt('23:55')
    ->timezone(Attendance::LOCAL_TIMEZONE)
    ->when(fn () => now(Attendance::LOCAL_TIMEZONE)->isLastOfMonth());
