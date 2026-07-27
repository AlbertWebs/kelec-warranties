<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyDocument;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, WarrantyDocument $document, AuditLogger $auditLogger): StreamedResponse
    {
        abort_unless($request->user()->can('warranties.view'), 403);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        $auditLogger->log('document_downloaded', $document->warranty, null, [
            'document_id' => $document->id,
            'type' => $document->type,
        ]);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }
}
