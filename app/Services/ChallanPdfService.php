<?php

namespace App\Services;

use App\Models\Challan;
use Barryvdh\DomPDF\Facade\Pdf;

class ChallanPdfService
{
    public function build(Challan $challan): string
    {
        $pdf = Pdf::loadView('pdf.challan', ['challan' => $challan]);
        return $pdf->output();
    }
}
