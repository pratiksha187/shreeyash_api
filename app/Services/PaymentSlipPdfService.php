<?php

namespace App\Services;

use App\Models\Payment;

class PaymentSlipPdfService
{
    public function build(Payment $payment): string
    {
        $user = $payment->user;
        $content = "0.2 w\n";
        $content .= $this->pdfText('Attendance Admin', 50, 805, 16);
        $content .= $this->pdfText('Payment Slip', 420, 805, 16);
        $content .= $this->pdfLine(50, 790, 545, 790);

        $content .= $this->pdfText('Employee Details', 50, 765, 12);
        $y = $this->pdfTable($content, 50, 748, [120, 160, 100, 115], [
            ['Employee Name', $user->name, 'Mobile', $user->mobile ?? '-'],
            ['Designation', $user->designation ?? '-', 'Employee ID', (string) $user->id],
            ['Period From', $payment->from_date->format('d M Y'), 'Period To', $payment->to_date->format('d M Y')],
        ]);

        $content .= $this->pdfText('Attendance Summary', 50, $y - 20, 12);
        $y = $this->pdfTable($content, 50, $y - 37, [165, 82, 165, 83], [
            ['Paid Days', (string) $payment->present_days, 'Present Days', (string) $payment->present_days_in_month],
            ['Week Offs', (string) $payment->weekoff_count, 'Leaves', (string) $payment->leave_total],
            ['Half Days', (string) $payment->half_day_count, 'C.Offs', (string) $payment->c_off_count],
        ]);

        $content .= $this->pdfText('Earnings', 50, $y - 20, 12);
        $content .= $this->pdfText('Deductions', 315, $y - 20, 12);

        $earningsY = $this->pdfTable($content, 50, $y - 37, [145, 100], [
            ['Gross Salary', 'Rs. ' . number_format((float) $payment->gross_salary, 2)],
            ['Per Day Rate', 'Rs. ' . number_format((float) $payment->per_day_rate, 2)],
            ['Basic 60%', 'Rs. ' . number_format((float) $payment->basic_60, 2)],
            ['HRA 5%', 'Rs. ' . number_format((float) $payment->hra_5, 2)],
            ['Conveyance 20%', 'Rs. ' . number_format((float) $payment->conveyance_20, 2)],
            ['Other Allowance', 'Rs. ' . number_format((float) $payment->other_allowance, 2)],
            ['Gross Payable', 'Rs. ' . number_format((float) $payment->gross_payable, 2)],
        ]);
        $deductionsY = $this->pdfTable($content, 315, $y - 37, [130, 100], [
            ['PF', 'Rs. ' . number_format((float) $payment->pf_12, 2)],
            ['Insurance', 'Rs. ' . number_format((float) $payment->insurance, 2)],
            ['PT', 'Rs. ' . number_format((float) $payment->pt, 2)],
            ['Advance', 'Rs. ' . number_format((float) $payment->advance, 2)],
            ['Total Deduction', 'Rs. ' . number_format((float) $payment->total_deduction, 2)],
        ]);
        $y = min($earningsY, $deductionsY) - 25;

        $this->pdfTable($content, 50, $y, [245, 250], [
            ['Net Payable', 'Rs. ' . number_format((float) $payment->net_payable, 2)],
        ], 30, 13);

        $content .= $this->pdfText('Generated on: ' . now()->format('d M Y h:i A'), 50, 55, 9);
        $content .= $this->pdfText('This is a system generated salary slip.', 315, 55, 9);

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function pdfText(string $text, float $x, float $y, int $size = 10): string
    {
        return "BT\n/F1 {$size} Tf\n{$x} {$y} Td\n(" . $this->escapePdfText($text) . ") Tj\nET\n";
    }

    private function pdfLine(float $x1, float $y1, float $x2, float $y2): string
    {
        return "{$x1} {$y1} m {$x2} {$y2} l S\n";
    }

    /**
     * @param array<int, float> $columnWidths
     * @param array<int, array<int, string>> $rows
     */
    private function pdfTable(string &$content, float $x, float $y, array $columnWidths, array $rows, int $rowHeight = 24, int $fontSize = 10): float
    {
        foreach ($rows as $row) {
            $cellX = $x;

            foreach ($columnWidths as $index => $width) {
                $content .= "{$cellX} " . ($y - $rowHeight) . " {$width} {$rowHeight} re S\n";
                $content .= $this->pdfText((string) ($row[$index] ?? ''), $cellX + 6, $y - 16, $fontSize);
                $cellX += $width;
            }

            $y -= $rowHeight;
        }

        return $y;
    }
}
