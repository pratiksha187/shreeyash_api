<?php

namespace App\Services;

use App\Models\Challan;
use Illuminate\Support\Facades\Storage;

class ChallanPdfService
{
    public function build(Challan $challan): string
    {
        $challan->loadMissing('user');

        $content = "0.2 w\n";
        $content .= $this->pdfText('Delivery Challan', 50, 805, 18);
        $content .= $this->pdfText('Generated on: ' . now()->format('d M Y h:i A'), 365, 805, 9);
        $content .= $this->pdfLine(50, 790, 545, 790);

        $y = 765;
        $y = $this->twoColumnRow(
            $content,
            $y,
            'Challan No.',
            (string) $challan->challan_no,
            'Date',
            $challan->challan_date?->format('d/m/Y') ?? '-'
        );
        $y = $this->fullRow($content, $y, 'Name Of Party', $challan->party_name);
        $y = $this->fullRow($content, $y, 'Material / M/c', $challan->material_machine);
        $y = $this->twoColumnRow($content, $y, 'Vehicle No.', $challan->vehicle_no, 'Measurement', $challan->measurement);
        $y = $this->fullRow($content, $y, 'Location', $challan->location);
        $y = $this->fullRow($content, $y, 'Time', $challan->delivery_time);
        $y = $this->twoColumnRow($content, $y, 'Receiver Name', $challan->receiver_name, 'Driver Name', $challan->driver_name);

        $submittedBy = trim(implode(' ', array_filter([
            $challan->user?->name,
            $challan->user?->mobile ? '(' . $challan->user->mobile . ')' : null,
            $challan->user?->designation ? '- ' . $challan->user->designation : null,
        ])));

        $this->fullRow($content, $y, 'Submitted By', $submittedBy ?: '-');

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

    public function store(Challan $challan): string
    {
        $relativePath = $this->relativePath($challan);

        Storage::disk('local')->makeDirectory(dirname($relativePath));
        Storage::disk('local')->put($relativePath, $this->build($challan));

        $challan->update(['pdf_file_path' => $relativePath]);

        return $relativePath;
    }

    public function fileName(Challan $challan): string
    {
        $safeChallanNo = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($challan->challan_no ?? 'challan'));
        $safeChallanNo = trim((string) $safeChallanNo, '-');

        return 'challan-' . $challan->id . '-' . ($safeChallanNo !== '' ? $safeChallanNo : 'challan') . '.pdf';
    }

    public function relativePath(Challan $challan): string
    {
        return 'challans/' . $challan->user_id . '/' . $this->fileName($challan);
    }

    private function fullRow(string &$content, float $y, string $label, ?string $value): float
    {
        $x = 50;
        $labelWidth = 125;
        $valueWidth = 370;
        $lines = $this->wrapText($value ?: '-', 72);
        $height = max(28, 14 + (count($lines) * 13));

        $content .= "{$x} " . ($y - $height) . " {$labelWidth} {$height} re S\n";
        $content .= ($x + $labelWidth) . ' ' . ($y - $height) . " {$valueWidth} {$height} re S\n";
        $content .= $this->pdfText($label, $x + 6, $y - 18, 10);

        $textY = $y - 18;
        foreach ($lines as $line) {
            $content .= $this->pdfText($line, $x + $labelWidth + 6, $textY, 10);
            $textY -= 13;
        }

        return $y - $height;
    }

    private function twoColumnRow(
        string &$content,
        float $y,
        string $labelA,
        ?string $valueA,
        string $labelB,
        ?string $valueB
    ): float {
        $x = 50;
        $height = 28;
        $widths = [90, 155, 95, 155];
        $values = [$labelA, $valueA ?: '-', $labelB, $valueB ?: '-'];

        foreach ($widths as $index => $width) {
            $content .= "{$x} " . ($y - $height) . " {$width} {$height} re S\n";
            $content .= $this->pdfText((string) $values[$index], $x + 6, $y - 18, 10);
            $x += $width;
        }

        return $y - $height;
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text, int $limit): array
    {
        $text = $this->normalizeText($text);
        $lines = [];

        foreach (explode("\n", wordwrap($text, $limit, "\n", true)) as $line) {
            $lines[] = $line !== '' ? $line : ' ';
        }

        return $lines ?: ['-'];
    }

    private function normalizeText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?: '-';

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

            if ($converted !== false) {
                return $converted;
            }
        }

        return $text;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->normalizeText($text));
    }

    private function pdfText(string $text, float $x, float $y, int $size = 10): string
    {
        return "BT\n/F1 {$size} Tf\n{$x} {$y} Td\n(" . $this->escapePdfText($text) . ") Tj\nET\n";
    }

    private function pdfLine(float $x1, float $y1, float $x2, float $y2): string
    {
        return "{$x1} {$y1} m {$x2} {$y2} l S\n";
    }
}
