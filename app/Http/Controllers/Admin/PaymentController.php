<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentSlipPdfService;
use App\Support\PaidHolidayCalendar;
use App\Support\Tenant;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        return view('admin.payments.index', [
            'employees' => User::query()->forCurrentCompany()->employees()->orderBy('name')->get(),
            'payments' => Payment::query()
                ->forCurrentCompany()
                ->with('user:id,name,mobile,designation')
                ->latest()
                ->paginate(15),
            'defaultFromDate' => now()->startOfMonth()->toDateString(),
            'defaultToDate' => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $usersTable = app(Tenant::class)->connectionName()
            ? app(Tenant::class)->connectionName().'.users'
            : 'users';

        $data = $request->validate([
            'user_id' => [
                'required',
                Rule::exists($usersTable, 'id')->where(fn ($query) => $query->where('role', 'employee')),
            ],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'ot_arrears_penalty' => ['nullable', 'numeric', 'min:-9999999999.99', 'max:9999999999.99'],
            'late_mark' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'loan_opening' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'loan_deduction' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();

        $existingPayment = Payment::query()
            ->forCurrentCompany()
            ->where('user_id', $data['user_id'])
            ->whereDate('from_date', $from->toDateString())
            ->whereDate('to_date', $to->toDateString())
            ->first();

        if ($existingPayment) {
            return back()->with('error', 'Payment is already generated for this period.');
        }

        $user = User::query()->forCurrentCompany()->employees()->findOrFail($data['user_id']);
        $payment = Payment::query()->create($this->calculatePayment($user, $from, $to, $data));

        $payment->load('user');
        $pdf = app(PaymentSlipPdfService::class)->build($payment);
        $fileName = 'payment-slip-' . $payment->user_id . '-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf';
        $relativePath = 'payments/' . $payment->user_id . '/' . $fileName;

        Storage::disk('local')->put($relativePath, $pdf);
        $payment->update(['pdf_file_path' => $relativePath]);

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Payment generated successfully.')
            ->with('payment_id', $payment->id);
    }

    public function slip(int $payment): Response
    {
        $payment = Payment::query()
            ->forCurrentCompany()
            ->with('user')
            ->findOrFail($payment);

        $pdf = app(PaymentSlipPdfService::class)->build($payment);
        $fileName = 'payment-slip-' . $payment->user_id . '-' . $payment->from_date->format('Ymd') . '-' . $payment->to_date->format('Ymd') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    private function calculatePayment(User $user, Carbon $from, Carbon $to, array $adjustments = []): array
    {
        $grossSalary = (float) ($user->salary ?? 0);
        $daysInMonth = max(1, $from->daysInMonth);
        $perDayRate = $grossSalary / $daysInMonth;

        $attendances = Attendance::query()
            ->forCurrentCompany()
            ->where('user_id', $user->id)
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
                $presentDates[$date] = true;
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

        $weekoffCount = 0;
        foreach (CarbonPeriod::create($from, '1 day', $to) as $date) {
            $dateString = $date->toDateString();

            if ($date->isSunday() && ! isset($holidayDates[$dateString])) {
                $weekoffCount++;
            }
        }

        $presentDays = count($presentDates);
        $halfDayCount = count($halfDayDates);
        $leaveTotal = count($leaveDates);
        $holidayCount = count($holidayDates);
        $cOffCount = 0;
        $paidDays = $presentDays + $weekoffCount + $holidayCount + $leaveTotal + $cOffCount + ($halfDayCount * 0.5);

        $attendancePayable = round($perDayRate * $paidDays, 2);
        $otArrearsPenalty = round((float) ($adjustments['ot_arrears_penalty'] ?? 0), 2);
        $grossPayable = round($attendancePayable + $otArrearsPenalty, 2);
        $basic60 = round($grossPayable * 0.6, 2);
        $hra5 = round($grossPayable * 0.05, 2);
        $conveyance20 = round($grossPayable * 0.2, 2);
        $otherAllowance = round($grossPayable - $basic60 - $hra5 - $conveyance20, 2);

        $pf = (float) ($user->pf ?? 0);
        $insurance = (float) ($user->insurance ?? 0);
        $pt = (float) ($user->pt ?? 0);
        $advance = (float) ($user->advance ?? 0);
        $lateMark = round((float) ($adjustments['late_mark'] ?? 0), 2);
        $loanOpening = round((float) ($adjustments['loan_opening'] ?? 0), 2);
        $loanDeduction = round((float) ($adjustments['loan_deduction'] ?? 0), 2);
        $loanClosing = max(0, round($loanOpening - $loanDeduction, 2));
        $totalDeduction = round($pf + $insurance + $pt + $lateMark + $advance + $loanDeduction, 2);
        $netPayable = round($grossPayable - $totalDeduction, 2);

        return [
            'company_id' => app(Tenant::class)->id(),
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

}
