<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentSlipPdfService;
use App\Support\PaidHolidayCalendar;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Payment $payment) => $this->paymentPayload($payment, false));

        return response()->json([
            'message' => 'Payment slips fetched successfully.',
            'payments' => $payments,
        ]);
    }

    public function show(Request $request, int $payment): JsonResponse
    {
        $payment = $this->findEmployeePayment($request, $payment);

        return response()->json([
            'message' => 'Payment slip fetched successfully.',
            'payment' => $this->paymentPayload($payment),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'ot_arrears_penalty' => ['nullable', 'numeric', 'min:-9999999999.99', 'max:9999999999.99'],
            'late_mark' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'loan_opening' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'loan_deduction' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();

        $user = $request->user();

        $existingPayment = Payment::query()
            ->forCurrentCompany()
            ->where('user_id', $user->id)
            ->whereDate('from_date', $from->toDateString())
            ->whereDate('to_date', $to->toDateString())
            ->first();

        if ($existingPayment) {
            $existingPayment->load('user');

            if (! $existingPayment->pdf_file_path) {
                $pdf = app(PaymentSlipPdfService::class)->build($existingPayment);
                $fileName = 'payment-slip-' . $existingPayment->user_id . '-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf';
                $relativePath = 'payments/' . $existingPayment->user_id . '/' . $fileName;
                Storage::disk('local')->put($relativePath, $pdf);
                $existingPayment->update(['pdf_file_path' => $relativePath]);
            }

            return response()->json([
                'message' => 'Payment slip already exists for this period.',
                'payment' => $this->paymentPayload($existingPayment),
                'pdf_file_path' => $existingPayment->pdf_file_path,
            ]);
        }

        $payment = Payment::query()->create($this->calculatePayment($user, $from, $to, $data));

        $payment->load('user');
        $pdf = app(PaymentSlipPdfService::class)->build($payment);
        $fileName = 'payment-slip-' . $payment->user_id . '-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf';
        $relativePath = 'payments/' . $payment->user_id . '/' . $fileName;

        Storage::disk('local')->put($relativePath, $pdf);
        $payment->update(['pdf_file_path' => $relativePath]);

        return response()->json([
            'message' => 'Payment slip generated and saved successfully.',
            'payment' => $this->paymentPayload($payment),
            'pdf_file_path' => $relativePath,
        ], 201);
    }

    private function calculatePayment(User $user, Carbon $from, Carbon $to, array $adjustments = []): array
    {
        $grossSalary = (float) ($user->salary ?? 0);
        $daysInMonth = max(1, $from->daysInMonth);
        $perDayRate = $grossSalary / $daysInMonth;

        $attendances = $user->attendances()
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('attendance_date')
            ->get();

        $presentDates = [];
        $halfDayDates = [];
        $leaveDates = [];
        $leaveDatesByType = [
            'casual' => [],
            'sick' => [],
            'paid' => [],
        ];
        $cOffCount = 0;

        foreach ($attendances as $attendance) {
            $date = $attendance->attendance_date->toDateString();

            if ($attendance->status === 'half_day' || $this->isPresentAttendanceHalfDay($attendance, $user)) {
                $halfDayDates[$date] = true;
            } elseif ($attendance->status === 'present') {
                $presentDates[$date] = true;
            } elseif ($attendance->status === 'leave') {
                $leaveDates[$date] = true;
                $leaveType = $attendance->leave_type ?? 'casual';

                if (array_key_exists($leaveType, $leaveDatesByType)) {
                    $leaveDatesByType[$leaveType][$date] = true;
                }
            }
        }

        $paidHolidays = PaidHolidayCalendar::holidaysBetween($from, $to);
        $holidayDates = [];
        foreach ($paidHolidays as $dateString => $holiday) {
            if (
                ! isset($presentDates[$dateString])
                && ! isset($halfDayDates[$dateString])
                && ! isset($leaveDates[$dateString])
            ) {
                $holidayDates[$dateString] = true;
            }
        }

        $actualSundayCount = 0;
        foreach (CarbonPeriod::create($from, '1 day', $to) as $date) {
            $dateString = $date->toDateString();

            if ($date->isSunday() && ! isset($holidayDates[$dateString])) {
                $actualSundayCount++;
            }
        }

        $weekoffCount = $this->weekoffCountForPeriod($from, $to, $actualSundayCount);

        $presentDays = count($presentDates);
        $halfDayCount = count($halfDayDates);
        $presentDaysInMonth = $presentDays + ($halfDayCount * 0.5);
        $leaveTotal = count($leaveDates);
        $holidayCount = count($holidayDates);
        $cOffCount = 0;
        $paidDays = $presentDaysInMonth + $weekoffCount + $holidayCount + $leaveTotal + $cOffCount;

        $attendancePayable = round($perDayRate * $paidDays, 2);
        $otArrearsPenalty = round((float) ($adjustments['ot_arrears_penalty'] ?? 0), 2);
        $grossPayable = round($attendancePayable + $otArrearsPenalty);
        $basic60 = round($grossSalary * 0.6, 2);
        $hra5 = round($grossSalary * 0.05, 2);
        $conveyance20 = round($grossSalary * 0.2, 2);
        $otherAllowance = round($grossSalary - $basic60 - $hra5 - $conveyance20, 2);

        $pf = (float) ($user->pf ?? 0);
        $insurance = (float) ($user->insurance ?? 0);
        $pt = (float) ($user->pt ?? 0);
        $advance = (float) ($user->advance ?? 0);
        $lateMark = round((float) ($adjustments['late_mark'] ?? 0), 2);
        $loanOpening = round((float) ($adjustments['loan_opening'] ?? 0), 2);
        $loanDeduction = round((float) ($adjustments['loan_deduction'] ?? 0), 2);
        $loanClosing = max(0, round($loanOpening - $loanDeduction, 2));
        $totalDeduction = round($pf + $insurance + $pt + $lateMark + $advance + $loanDeduction, 2);
        $netPayable = round($grossPayable - $totalDeduction);

        return [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'present_days' => $paidDays,
            'present_days_in_month' => $presentDaysInMonth,
            'weekoff_count' => $weekoffCount,
            'holiday_count' => $holidayCount,
            'c_off_count' => $cOffCount,
            'leave_cl' => count($leaveDatesByType['casual']),
            'leave_sl' => count($leaveDatesByType['sick']),
            'leave_el' => count($leaveDatesByType['paid']),
            'leave_total' => $leaveTotal,
            'half_day_count' => $halfDayCount,
            'gross_salary' => $grossSalary,
            'per_day_rate' => round($perDayRate, 2),
            'basic_60' => $basic60,
            'hra_5' => $hra5,
            'conveyance_20' => $conveyance20,
            'other_allowance' => $otherAllowance,
            'ot_arrears_penalty' => $otArrearsPenalty,
            'gross_payable' => $grossPayable,
            'pf_12' => $pf,
            'insurance' => $insurance,
            'pt' => $pt,
            'late_mark' => $lateMark,
            'advance' => $advance,
            'loan_opening' => $loanOpening,
            'loan_deduction' => $loanDeduction,
            'loan_closing' => $loanClosing,
            'total_deduction' => $totalDeduction,
            'net_payable' => $netPayable,
        ];
    }

    private function weekoffCountForPeriod(Carbon $from, Carbon $to, int $actualSundayCount): int
    {
        $isFullMonth = $from->isSameDay($from->copy()->startOfMonth())
            && $to->isSameDay($from->copy()->endOfMonth());

        if (! $isFullMonth) {
            return $actualSundayCount;
        }

        return max(0, $from->daysInMonth - 26);
    }

    private function isPresentAttendanceHalfDay(Attendance $attendance, User $user): bool
    {
        if ($attendance->status !== 'present') {
            return false;
        }

        $checkIn = $attendance->localCheckInAt();
        $checkOut = $attendance->localCheckOutAt();

        if (! $checkIn || ! $checkOut || ! $attendance->attendance_date) {
            return false;
        }

        $workDate = $attendance->attendance_date->copy();
        $start = $workDate->copy()->setTimeFrom($checkIn);
        $end = $workDate->copy()->setTimeFrom($checkOut);

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        $workedMinutes = (int) $start->diffInMinutes($end);

        return $workedMinutes >= 240
            && $workedMinutes <= (int) ceil($this->expectedWorkMinutes($user) / 2);
    }

    private function expectedWorkMinutes(User $user): int
    {
        return max(1, (int) round(((float) ($user->hours_per_day ?: 9)) * 60));
    }

    public function slip(Request $request, int $payment): Response
    {
        $payment = $this->findEmployeePayment($request, $payment);

        $payment->load('user');
        $pdf = app(PaymentSlipPdfService::class)->build($payment);
        $fileName = 'payment-slip-' . $payment->user_id . '-' . $payment->from_date->format('Ymd') . '-' . $payment->to_date->format('Ymd') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function slipData(Request $request, int $payment): JsonResponse
    {
        $payment = $this->findEmployeePayment($request, $payment);

        $payment->load('user');
        $pdf = app(PaymentSlipPdfService::class)->build($payment);
        $fileName = 'payment-slip-' . $payment->user_id . '-' . $payment->from_date->format('Ymd') . '-' . $payment->to_date->format('Ymd') . '.pdf';

        return response()->json([
            'message' => 'Payment slip PDF fetched successfully.',
            'payment_id' => $payment->id,
            'file_name' => $fileName,
            'mime_type' => 'application/pdf',
            'pdf_base64' => base64_encode($pdf),
            'pdf_url' => route('api.payments.slip', $payment),
            'pdf_path' => '/api/payments/' . $payment->id . '/slip',
        ]);
    }

    private function paymentPayload(Payment $payment, bool $includeDetails = true): array
    {
        $payload = [
            'id' => $payment->id,
            'from_date' => $payment->from_date?->toDateString(),
            'to_date' => $payment->to_date?->toDateString(),
            'period' => [
                'from' => $payment->from_date?->format('d M Y'),
                'to' => $payment->to_date?->format('d M Y'),
            ],
            'paid_days' => $payment->present_days,
            'gross_payable' => $payment->gross_payable,
            'total_deduction' => $payment->total_deduction,
            'net_payable' => $payment->net_payable,
            'pdf_url' => route('api.payments.slip', $payment),
            'pdf_path' => '/api/payments/' . $payment->id . '/slip',
            'pdf_data_path' => '/api/payments/' . $payment->id . '/slip-data',
            'pdf_file_path' => $payment->pdf_file_path,
            'generated_at' => $payment->created_at,
        ];

        if (! $includeDetails) {
            return $payload;
        }

        return array_merge($payload, [
            'attendance_summary' => [
                'paid_days' => $payment->present_days,
                'present_days' => $payment->present_days_in_month,
                'week_offs' => $payment->weekoff_count,
                'holidays' => $payment->holiday_count,
                'c_offs' => $payment->c_off_count,
                'leaves' => $payment->leave_total,
                'leave_cl' => $payment->leave_cl,
                'leave_sl' => $payment->leave_sl,
                'leave_el' => $payment->leave_el,
                'half_days' => $payment->half_day_count,
            ],
            'earnings' => [
                'gross_salary' => $payment->gross_salary,
                'per_day_rate' => $payment->per_day_rate,
                'basic_60' => $payment->basic_60,
                'hra_5' => $payment->hra_5,
                'conveyance_20' => $payment->conveyance_20,
                'other_allowance' => $payment->other_allowance,
                'ot_arrears_penalty' => $payment->ot_arrears_penalty,
                'gross_payable' => $payment->gross_payable,
            ],
            'deductions' => [
                'pf' => $payment->pf_12,
                'insurance' => $payment->insurance,
                'pt' => $payment->pt,
                'late_mark' => $payment->late_mark,
                'advance' => $payment->advance,
                'loan_opening' => $payment->loan_opening,
                'loan_deduction' => $payment->loan_deduction,
                'loan_closing' => $payment->loan_closing,
                'total_deduction' => $payment->total_deduction,
            ],
            'net_payable' => $payment->net_payable,
        ]);
    }

    private function findEmployeePayment(Request $request, int $paymentId): Payment
    {
        return Payment::query()
            ->forCurrentCompany()
            ->where('user_id', $request->user()->id)
            ->findOrFail($paymentId);
    }
}
