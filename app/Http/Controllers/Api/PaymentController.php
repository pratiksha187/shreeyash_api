<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentSlipPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Payment $payment) => $this->paymentPayload($payment, false));

        return response()->json([
            'message' => 'Payment slips fetched successfully.',
            'payments' => $payments,
        ]);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->user_id !== $request->user()->id) {
            abort(404);
        }

        return response()->json([
            'message' => 'Payment slip fetched successfully.',
            'payment' => $this->paymentPayload($payment),
        ]);
    }

    public function slip(Request $request, Payment $payment): Response
    {
        if ($payment->user_id !== $request->user()->id) {
            abort(404);
        }

        $payment->load('user');
        $pdf = app(PaymentSlipPdfService::class)->build($payment);
        $fileName = 'payment-slip-' . $payment->user_id . '-' . $payment->from_date->format('Ymd') . '-' . $payment->to_date->format('Ymd') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
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
}
