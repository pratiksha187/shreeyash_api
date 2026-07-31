<?php

namespace App\Services;

use App\Mail\MonthlyAttendanceReportMail;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\User;
use App\Support\Tenant;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class MonthlyAttendanceReportMailer
{
    public function sendForMonth(Carbon|string|null $month = null, ?int $companyId = null): array
    {
        $monthStart = $month
            ? Carbon::parse($month, Attendance::LOCAL_TIMEZONE)->startOfMonth()
            : Carbon::now(Attendance::LOCAL_TIMEZONE)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();

        $companies = Company::query()
            ->where('status', 'active')
            ->when($companyId, fn ($query) => $query->whereKey($companyId))
            ->orderBy('id')
            ->get();

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($companies as $company) {
            app(Tenant::class)->set($company);

            $employees = User::query()
                ->forCurrentCompany()
                ->employees()
                ->where('is_active', true)
                ->whereNotNull('email')
                ->orderBy('name')
                ->get();

            foreach ($employees as $employee) {
                if (! filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    continue;
                }

                try {
                    $report = $this->buildReport($employee, $monthStart, $monthEnd);

                    Mail::to($employee->email)
                        ->cc($this->ccRecipients())
                        ->send(new MonthlyAttendanceReportMail(
                            $company,
                            $employee,
                            $monthStart->copy(),
                            $monthEnd->copy(),
                            $report['rows'],
                            $report['summary'],
                            $report['missingItems']
                        ));

                    $sent++;
                } catch (\Throwable $exception) {
                    report($exception);
                    $failed++;
                }
            }
        }

        app(Tenant::class)->set(null);

        return [
            'companies' => $companies->count(),
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'month' => $monthStart->format('Y-m'),
        ];
    }

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, summary: array<string, int>, missingItems: Collection<int, array<string, mixed>>}
     */
    private function buildReport(User $employee, Carbon $monthStart, Carbon $monthEnd): array
    {
        $attendances = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $employee->id)
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('attendance_date')
            ->get()
            ->keyBy(fn (Attendance $attendance) => $attendance->attendance_date?->toDateString());

        $summary = [
            'present' => 0,
            'absent' => 0,
            'leave' => 0,
            'half_day' => 0,
            'not_marked' => 0,
            'missing_login' => 0,
            'missing_logout' => 0,
        ];

        $rows = collect();
        $missingItems = collect();

        foreach (CarbonPeriod::create($monthStart, $monthEnd) as $date) {
            $dateString = $date->toDateString();
            $attendance = $attendances->get($dateString);
            $status = $attendance?->status ?? 'not_marked';
            $checkIn = $attendance?->localCheckInAt();
            $checkOut = $attendance?->localCheckOutAt();

            if (! array_key_exists($status, $summary)) {
                $summary[$status] = 0;
            }

            $summary[$status]++;

            $missingForDay = [];

            if (! $attendance || ($status === 'present' && ! $checkIn)) {
                $summary['missing_login']++;
                $missingForDay[] = 'login';
            }

            if ($attendance && in_array($status, ['present', 'half_day'], true) && ! $checkOut) {
                $summary['missing_logout']++;
                $missingForDay[] = 'logout';
            }

            if ($missingForDay !== []) {
                $missingItems->push([
                    'date' => $date->copy(),
                    'missing' => $missingForDay,
                ]);
            }

            $rows->push([
                'date' => $date->copy(),
                'status' => $this->statusLabel($status),
                'login' => $checkIn?->format('h:i A') ?? '-',
                'logout' => $checkOut?->format('h:i A') ?? '-',
                'total' => $this->totalHours($checkIn, $checkOut),
                'remarks' => $attendance?->remarks ?? '',
            ]);
        }

        return [
            'rows' => $rows,
            'summary' => $summary,
            'missingItems' => $missingItems,
        ];
    }

    private function ccRecipients(): array
    {
        return collect(config('attendance.monthly_report.cc', []))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }

    private function statusLabel(string $status): string
    {
        return str_replace(' ', ' ', ucwords(str_replace('_', ' ', $status)));
    }

    private function totalHours(?Carbon $checkIn, ?Carbon $checkOut): string
    {
        if (! $checkIn || ! $checkOut || $checkOut->lessThan($checkIn)) {
            return '-';
        }

        $minutes = (int) $checkIn->diffInMinutes($checkOut);

        return sprintf('%02d:%02d hrs', intdiv($minutes, 60), $minutes % 60);
    }
}
