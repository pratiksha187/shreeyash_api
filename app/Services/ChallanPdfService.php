<?php

namespace App\Services;

use App\Models\Challan;
use Barryvdh\DomPDF\Facade as PDF;

class ChallanPdfService
{
    public function build(Challan $challan): string
    {
        $pdf = PDF::loadView('pdf.challan', ['challan' => $challan]);
        return $pdf->output();
    }
}
