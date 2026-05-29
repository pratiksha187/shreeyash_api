<?php

namespace App\Services;

use App\Models\Payment;

class PaymentSlipPdfService
{
    public function build(Payment $payment): string
    {
        $user = $payment->user;
        $content = "0.4 w\n";
        $content .= $this->pdfStrokeColor(0.72, 0.72, 0.72);
        $content .= $this->pdfRect(35, 35, 525, 772);
        $content .= $this->pdfFillColor(0.95, 0.95, 0.93);
        $content .= $this->pdfRect(35, 730, 525, 77, true);
        $content .= $this->pdfFillColor(0.85, 0.63, 0.12);
        $content .= $this->pdfRect(35, 730, 525, 9, true);

        $content .= $this->pdfCompanyLogo(62, 748);
        $content .= $this->pdfCenteredText('Shreeyash Construction', 307, 782, 20, 'F2', [0.20, 0.20, 0.20]);
        $content .= $this->pdfCenteredText('Khopoli, Tal- Khalapur, Dist - Raigad', 307, 762, 11, 'F2', [0.28, 0.28, 0.28]);
        $content .= $this->pdfCenteredText('Contact No. 9923299301 / 9326216153', 307, 746, 11, 'F2', [0.28, 0.28, 0.28]);

        $content .= $this->pdfFillColor(0.30, 0.30, 0.30);
        $content .= $this->pdfRect(220, 705, 155, 28, true);
        $content .= $this->pdfFillColor(0.85, 0.63, 0.12);
        $content .= $this->pdfRect(220, 705, 155, 5, true);
        $content .= $this->pdfCenteredText('SALARY SLIP', 297.5, 715, 15, 'F2', [1, 1, 1]);

        $sectionY = 676;
        $content .= $this->pdfSectionTitle('Employee Details', 50, $sectionY, 495);
        $y = $this->pdfTable($content, 50, $sectionY - 28, [120, 160, 100, 115], [
            ['Employee Name', $user->name, 'Mobile', $user->mobile ?? '-'],
            ['Designation', $user->designation ?? '-', 'Employee ID', (string) $user->id],
            ['Period From', $payment->from_date->format('d M Y'), 'Period To', $payment->to_date->format('d M Y')],
        ], 24, 10, [0, 2]);

        $sectionY = $y - 32;
        $content .= $this->pdfSectionTitle('Attendance Summary', 50, $sectionY, 495);
        $y = $this->pdfTable($content, 50, $sectionY - 28, [165, 82, 165, 83], [
            ['Paid Days', (string) $payment->present_days, 'Present Days', (string) $payment->present_days_in_month],
            ['Week Offs', (string) $payment->weekoff_count, 'Leaves', (string) $payment->leave_total],
            ['Half Days', (string) $payment->half_day_count, 'C.Offs', (string) $payment->c_off_count],
        ], 24, 10, [0, 2]);

        $sectionY = $y - 32;
        $content .= $this->pdfSectionTitle('Earnings', 50, $sectionY, 245);
        $content .= $this->pdfSectionTitle('Deductions', 315, $sectionY, 230);

        $tableY = $sectionY - 28;
        $earningsY = $this->pdfTable($content, 50, $tableY, [145, 100], [
            ['Gross Salary', 'Rs. ' . number_format((float) $payment->gross_salary, 2)],
            ['Per Day Rate', 'Rs. ' . number_format((float) $payment->per_day_rate, 2)],
            ['Basic 60%', 'Rs. ' . number_format((float) $payment->basic_60, 2)],
            ['HRA 5%', 'Rs. ' . number_format((float) $payment->hra_5, 2)],
            ['Conveyance 20%', 'Rs. ' . number_format((float) $payment->conveyance_20, 2)],
            ['Other Allowance', 'Rs. ' . number_format((float) $payment->other_allowance, 2)],
            ['Gross Payable', 'Rs. ' . number_format((float) $payment->gross_payable, 2)],
        ], 24, 10, [0]);
        $deductionsY = $this->pdfTable($content, 315, $tableY, [130, 100], [
            ['PF', 'Rs. ' . number_format((float) $payment->pf_12, 2)],
            ['Insurance', 'Rs. ' . number_format((float) $payment->insurance, 2)],
            ['PT', 'Rs. ' . number_format((float) $payment->pt, 2)],
            ['Advance', 'Rs. ' . number_format((float) $payment->advance, 2)],
            ['Total Deduction', 'Rs. ' . number_format((float) $payment->total_deduction, 2)],
        ], 24, 10, [0]);
        $y = min($earningsY, $deductionsY) - 38;

        $content .= $this->pdfFillColor(0.96, 0.91, 0.76);
        $content .= $this->pdfRect(50, $y - 30, 495, 34, true);
        $this->pdfTable($content, 50, $y, [245, 250], [
            ['Net Payable', 'Rs. ' . number_format((float) $payment->net_payable, 2)],
        ], 30, 13, [0]);

        $content .= $this->pdfStrokeColor(0.72, 0.72, 0.72);
        $content .= $this->pdfLine(50, 76, 545, 76);
        $content .= $this->pdfText('Generated on: ' . now()->format('d M Y h:i A'), 50, 58, 9, 'F1', [0.28, 0.28, 0.28]);
        $content .= $this->pdfText('This is a system generated salary slip.', 348, 58, 9, 'F1', [0.28, 0.28, 0.28]);
        $content .= $this->pdfCenteredText('Powered by ConstructKaro', 297.5, 42, 9, 'F2', [0.85, 0.63, 0.12]);

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 6 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n",
            "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n",
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

    private function pdfText(string $text, float $x, float $y, int $size = 10, string $font = 'F1', array $color = [0, 0, 0]): string
    {
        return "BT\n{$color[0]} {$color[1]} {$color[2]} rg\n/{$font} {$size} Tf\n{$x} {$y} Td\n(" . $this->escapePdfText($text) . ") Tj\nET\n";
    }

    private function pdfCenteredText(string $text, float $centerX, float $y, int $size = 10, string $font = 'F1', array $color = [0, 0, 0]): string
    {
        $estimatedWidth = strlen($text) * $size * 0.5;

        return $this->pdfText($text, $centerX - ($estimatedWidth / 2), $y, $size, $font, $color);
    }

    private function pdfLine(float $x1, float $y1, float $x2, float $y2): string
    {
        return "{$x1} {$y1} m {$x2} {$y2} l S\n";
    }

    private function pdfRect(float $x, float $y, float $width, float $height, bool $fill = false): string
    {
        return "{$x} {$y} {$width} {$height} re " . ($fill ? "f\n" : "S\n");
    }

    private function pdfFillColor(float $red, float $green, float $blue): string
    {
        return "{$red} {$green} {$blue} rg\n";
    }

    private function pdfStrokeColor(float $red, float $green, float $blue): string
    {
        return "{$red} {$green} {$blue} RG\n";
    }

    private function pdfSectionTitle(string $title, float $x, float $y, float $width): string
    {
        $content = $this->pdfFillColor(0.30, 0.30, 0.30);
        $content .= $this->pdfRect($x, $y, $width, 20, true);
        $content .= $this->pdfFillColor(0.85, 0.63, 0.12);
        $content .= $this->pdfRect($x, $y, 5, 20, true);
        $content .= $this->pdfText($title, $x + 8, $y + 6, 11, 'F2', [1, 1, 1]);

        return $content;
    }

    private function pdfCompanyLogo(float $x, float $y): string
    {
        $content = $this->pdfFillColor(0.85, 0.63, 0.12);
        $content .= $this->pdfRect($x, $y, 46, 42, true);
        $content .= $this->pdfFillColor(0.30, 0.30, 0.30);
        $content .= $this->pdfRect($x + 6, $y + 7, 34, 24, true);
        $content .= $this->pdfFillColor(0.95, 0.95, 0.93);
        $content .= $this->pdfRect($x + 11, $y + 12, 6, 14, true);
        $content .= $this->pdfRect($x + 20, $y + 12, 6, 14, true);
        $content .= $this->pdfRect($x + 29, $y + 12, 6, 14, true);
        $content .= $this->pdfText('SC', $x + 9, $y + 30, 13, 'F2', [1, 1, 1]);

        return $content;
    }

    /**
     * @param array<int, float> $columnWidths
     * @param array<int, array<int, string>> $rows
     * @param array<int, int> $labelColumns
     */
    private function pdfTable(string &$content, float $x, float $y, array $columnWidths, array $rows, int $rowHeight = 24, int $fontSize = 10, array $labelColumns = []): float
    {
        foreach ($rows as $rowIndex => $row) {
            $cellX = $x;

            foreach ($columnWidths as $index => $width) {
                if ($rowIndex % 2 === 0) {
                    $content .= $this->pdfFillColor(0.98, 0.98, 0.96);
                    $content .= $this->pdfRect($cellX, $y - $rowHeight, $width, $rowHeight, true);
                }

                $content .= $this->pdfStrokeColor(0.78, 0.78, 0.76);
                $content .= "{$cellX} " . ($y - $rowHeight) . " {$width} {$rowHeight} re S\n";
                $isLabel = in_array($index, $labelColumns, true);
                $content .= $this->pdfText(
                    (string) ($row[$index] ?? ''),
                    $cellX + 6,
                    $y - 16,
                    $fontSize,
                    $isLabel ? 'F2' : 'F1',
                    $isLabel ? [0.18, 0.18, 0.18] : [0.12, 0.12, 0.12]
                );
                $cellX += $width;
            }

            $y -= $rowHeight;
        }

        return $y;
    }
}
