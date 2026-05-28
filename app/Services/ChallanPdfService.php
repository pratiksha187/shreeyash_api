<?php

namespace App\Services;

use App\Models\Challan;
use Illuminate\Support\Facades\Storage;

class ChallanPdfService
{
    public function build(Challan $challan): string
    {
        $challan->loadMissing('user');

        $formX = 72;
        $formY = 78;
        $formW = 451;
        $formH = 700;
        $right = $formX + $formW;
        $center = $formX + ($formW / 2);
        $top = $formY + $formH;

        $content = "0 0 0 RG\n0 0 0 rg\n";
        $content .= "0.8 w\n";
        $content .= $this->pdfRect($formX, $formY, $formW, $formH);

        $content .= $this->pdfCenteredText('DELIVERY CHALLAN', $center, $top - 18, 12, 'F2');
        $content .= $this->pdfCenteredText('Shreeyash Construction', $center, $top - 41, 22, 'F4');
        $content .= $this->pdfCenteredText('Khopoli, Tal- Khalapur, Dist - Raigad', $center, $top - 62, 10, 'F2');
        $content .= $this->pdfCenteredText('Contact No. 9923299301 / 9326216153', $center, $top - 79, 10, 'F2');
        $content .= $this->pdfLine($formX, $top - 94, $right, $top - 94);

        $this->formField(
            $content,
            'Challan No.  :',
            (string) $challan->challan_no,
            92,
            176,
            261,
            $top - 128,
            14,
            12,
        );

        $this->formField(
            $content,
            'Date:',
            $challan->challan_date?->format('d/m/Y') ?? '-',
            314,
            354,
            505,
            $top - 128,
            13,
            16,
        );

        $this->formField($content, 'Name Of Party :', $challan->party_name, 92, 205, 505, $top - 185, 13, 34);
        $this->formField($content, 'Material /M/c. :', $challan->material_machine, 92, 205, 505, $top - 242, 13, 34);
        $this->formField($content, 'Vehical No.     :', $challan->vehicle_no, 92, 205, 505, $top - 299, 13, 34);
        $this->formField($content, 'Measurement   :', $challan->measurement, 92, 205, 505, $top - 356, 13, 34);
        $this->formField($content, 'Location          :', $challan->location, 92, 205, 505, $top - 413, 13, 34);
        $this->formField($content, 'Time               :', $this->formatDeliveryTime($challan->delivery_time), 92, 205, 505, $top - 484, 13, 34);

        $content .= $this->pdfLine(106, $formY + 50, 215, $formY + 50);
        $content .= $this->pdfLine(368, $formY + 50, 477, $formY + 50);

        if ($challan->receiver_name) {
            $content .= $this->pdfCenteredText($challan->receiver_name, 160, $formY + 58, 11, 'F3', '0.05 0.12 0.45');
        }

        if ($challan->driver_name) {
            $content .= $this->pdfCenteredText($challan->driver_name, 422, $formY + 58, 11, 'F3', '0.05 0.12 0.45');
        }

        $content .= $this->pdfCenteredText('Receiver Sign.', 160, $formY + 28, 11, 'F2');
        $content .= $this->pdfCenteredText('Driver Sign.', 422, $formY + 28, 11, 'F2');

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R /F4 7 0 R >> >> /Contents 8 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n",
            "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique >>\nendobj\n",
            "7 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-BoldOblique >>\nendobj\n",
            "8 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n",
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

    private function formField(
        string &$content,
        string $label,
        ?string $value,
        float $labelX,
        float $lineStartX,
        float $lineEndX,
        float $lineY,
        int $fontSize,
        int $wrapLimit
    ): void {
        $content .= $this->pdfText($label, $labelX, $lineY + 5, 11);

        foreach ($this->wrapText($value ?: '-', $wrapLimit) as $index => $line) {
            $currentLineY = $lineY - ($index * 18);
            $content .= $this->pdfLine($lineStartX, $currentLineY, $lineEndX, $currentLineY);
            $content .= $this->pdfText($line, $lineStartX + 14, $currentLineY + 5, $fontSize, 'F3', '0.05 0.12 0.45');
        }
    }

    private function formatDeliveryTime(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return preg_replace('/\s*,\s*/', "\n", trim($value)) ?: '-';
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text, int $limit): array
    {
        $lines = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($text) ?: '-') as $paragraph) {
            $paragraph = $this->normalizeText($paragraph);

            foreach (explode("\n", wordwrap($paragraph, $limit, "\n", true)) as $line) {
                $lines[] = $line !== '' ? $line : ' ';
            }
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

    private function pdfText(string $text, float $x, float $y, int $size = 10, string $font = 'F1', string $color = '0 0 0'): string
    {
        return "q\n{$color} rg\nBT\n/{$font} {$size} Tf\n{$x} {$y} Td\n(" . $this->escapePdfText($text) . ") Tj\nET\nQ\n";
    }

    private function pdfCenteredText(string $text, float $centerX, float $y, int $size = 10, string $font = 'F1', string $color = '0 0 0'): string
    {
        $x = $centerX - $this->textWidth($text, $size, $font) / 2;

        return $this->pdfText($text, $x, $y, $size, $font, $color);
    }

    private function textWidth(string $text, int $size, string $font): float
    {
        $factor = in_array($font, ['F2', 'F4'], true) ? 0.58 : 0.52;

        return strlen($this->normalizeText($text)) * $size * $factor;
    }

    private function pdfLine(float $x1, float $y1, float $x2, float $y2): string
    {
        return "{$x1} {$y1} m {$x2} {$y2} l S\n";
    }

    private function pdfRect(float $x, float $y, float $width, float $height): string
    {
        return "{$x} {$y} {$width} {$height} re S\n";
    }
}
