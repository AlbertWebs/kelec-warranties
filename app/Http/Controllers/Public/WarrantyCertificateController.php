<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Warranty;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WarrantyCertificateController extends Controller
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function show(string $reference): View
    {
        $warranty = Warranty::with(['customer', 'product', 'purchaseSource'])
            ->where('reference', $reference)
            ->firstOrFail();

        return view('public.certificate.show', compact('warranty'));
    }

    public function download(string $reference): Response
    {
        $warranty = Warranty::with(['customer', 'product', 'purchaseSource'])
            ->where('reference', $reference)
            ->firstOrFail();

        $this->auditLogger->log('warranty_certificate_downloaded', $warranty);

        $pdf = Pdf::loadView('public.certificate.pdf', compact('warranty'));

        return $pdf->download($warranty->reference.'-certificate.pdf');
    }
}
