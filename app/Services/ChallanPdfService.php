<?php

namespace App\Services;

use App\Models\Challan;

class ChallanPdfService
{
    public function build(Challan $challan): string
    {
        $user = $challan->user;
        $content = "0.2 w\n";
        $content .= $this->pdfText('Challan', 50, 805, 18);
        $content .= $this->pdfText('Generated from Attendance System', 50, 785, 10);
        $content .= $this->pdfLine(50, 776, 545, 776);

        $rows = [
            ['Challan No.', $challan->challan_no ?? '-'],
            ['Date', $challan->challan_date?->format('d/m/Y') ?? '-'],
            ['Party Name', $challan->party_name ?? '-'],
            ['Material / M/c', $challan->material_machine ?? '-'],
            ['Vehicle No.', $challan->vehicle_no ?? '-'],
            ['Measurement', $challan->measurement ?? '-'],
            ['Location', $challan->location ?? '-'],
            ['Delivery Time', $challan->delivery_time ?? '-'],
            ['Receiver Name', $challan->receiver_name ?? '-'],
            ['Driver Name', $challan->driver_name ?? '-'],
            ['Submitted By', $user?->name ?? '-'],
            ['Mobile', $user?->mobile ?? '-'],
        ];

        $y = $this->pdfTable($content, 50, 742, [170, 330], $rows, 26, 11);

        $content .= $this->pdfText('Generated on: ' . now()->format('d M Y h:i A'), 50, max(45, $y - 10), 9);
        $content .= $this->pdfText('This is a system generated challan document.', 315, max(45, $y - 10), 9);

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
