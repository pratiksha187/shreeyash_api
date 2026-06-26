<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentSlipPdfService;
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

        $payment = Payment::query()->create($this->calculatePayment($user, $from, $to));

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

    private function calculatePayment(User $user, Carbon $from, Carbon $to): array
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

            if ($attendance->status === 'present') {
                if ($attendance->attendance_date->isSunday()) {
                    $cOffCount++;
                } else {
                    $presentDates[$date] = true;
                }
            } elseif ($attendance->status === 'half_day') {
                $halfDayDates[$date] = true;
            } elseif ($attendance->status === 'leave') {
                $leaveDates[$date] = true;
                $leaveType = $attendance->leave_type ?? 'casual';

                if (array_key_exists($leaveType, $leaveDatesByType)) {
                    $leaveDatesByType[$leaveType][$date] = true;
                }
            }
        }

        $weekoffCount = 0;
        foreach (CarbonPeriod::create($from, '1 day', $to) as $date) {
            $dateString = $date->toDateString();

            if ($date->isSunday() && ! isset($presentDates[$dateString]) && ! isset($halfDayDates[$dateString])) {
                $weekoffCount++;
            }
        }

        $presentDays = count($presentDates);
        $halfDayCount = count($halfDayDates);
        $leaveTotal = count($leaveDates);
        $holidayCount = 0;
        $paidDays = $presentDays + $weekoffCount + $holidayCount + $leaveTotal + $cOffCount + ($halfDayCount * 0.5);

        $grossPayable = round($perDayRate * $paidDays, 2);
        $basic60 = round($grossPayable * 0.6, 2);
        $hra5 = round($grossPayable * 0.05, 2);
        $conveyance20 = round($grossPayable * 0.2, 2);
        $otherAllowance = round($grossPayable - $basic60 - $hra5 - $conveyance20, 2);

        $pf = (float) ($user->pf ?? 0);
        $insurance = (float) ($user->insurance ?? 0);
        $pt = (float) ($user->pt ?? 0);
        $advance = (float) ($user->advance ?? 0);
        $totalDeduction = round($pf + $insurance + $pt + $advance, 2);
        $netPayable = round($grossPayable - $totalDeduction, 2);

        return [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'present_days' => $paidDays,
            'present_days_in_month' => $presentDays,
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
            'gross_payable' => $grossPayable,
            'pf_12' => $pf,
            'insurance' => $insurance,
            'pt' => $pt,
            'advance' => $advance,
            'total_deduction' => $totalDeduction,
            'net_payable' => $netPayable,
        ];
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
                'gross_payable' => $payment->gross_payable,
            ],
            'deductions' => [
                'pf' => $payment->pf_12,
                'insurance' => $payment->insurance,
                'pt' => $payment->pt,
                'advance' => $payment->advance,
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
