<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MonthlyAttendanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, int>  $summary
     * @param  Collection<int, array<string, mixed>>  $missingItems
     */
    public function __construct(
        public Company $company,
        public User $employee,
        public Carbon $monthStart,
        public Carbon $monthEnd,
        public Collection $rows,
        public array $summary,
        public Collection $missingItems
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Monthly Attendance Report - '.$this->monthStart->format('F Y')
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.monthly-attendance-report'
        );
    }
}
