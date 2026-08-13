<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\WarrantyClaim;
use App\Models\WarrantyClaimPhoto;
use App\Services\AuditLogger;
use App\Services\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimController extends Controller
{
    public function __construct(
        protected NotificationDispatcher $notificationDispatcher,
        protected AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('claims.view'), 403);

        $claims = WarrantyClaim::query()
            ->with(['customer', 'warranty'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.claims.index', [
            'claims' => $claims,
            'statuses' => ClaimStatus::cases(),
        ]);
    }

    public function show(Request $request, WarrantyClaim $claim): View
    {
        abort_unless($request->user()->can('claims.view'), 403);

        $claim->load(['customer', 'warranty.product', 'photos']);

        return view('admin.claims.show', [
            'claim' => $claim,
            'statuses' => ClaimStatus::cases(),
        ]);
    }

    public function photo(Request $request, WarrantyClaim $claim, WarrantyClaimPhoto $photo): StreamedResponse
    {
        abort_unless($request->user()->can('claims.view'), 403);
        abort_unless($photo->warranty_claim_id === $claim->id, 404);
        abort_unless($photo->existsOnDisk(), 404);

        return Storage::disk($photo->disk)->response($photo->path, $photo->original_name, [
            'Content-Type' => $photo->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function update(Request $request, WarrantyClaim $claim): RedirectResponse
    {
        abort_unless($request->user()->can('claims.manage'), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ClaimStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'notify_customer' => ['nullable', 'boolean'],
        ]);

        $previousStatus = $claim->status instanceof ClaimStatus
            ? $claim->status
            : ClaimStatus::from((string) $claim->status);

        $nextStatus = $validated['status'] instanceof ClaimStatus
            ? $validated['status']
            : ClaimStatus::from((string) $validated['status']);

        $previousNotes = $claim->admin_notes;
        $statusChanged = $previousStatus !== $nextStatus;
        $shouldNotify = $statusChanged && $request->boolean('notify_customer', true);

        $claim->update([
            'status' => $nextStatus,
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        $this->auditLogger->log('claim_updated', $claim->warranty, [
            'status' => $previousStatus->value,
            'admin_notes' => $previousNotes,
        ], [
            'claim_id' => $claim->id,
            'claim_reference' => $claim->reference,
            'status' => $nextStatus->value,
            'notified' => $shouldNotify,
        ]);

        if ($shouldNotify) {
            $this->notificationDispatcher->notifyClaimStatusChange($claim->fresh(['customer', 'warranty']), $previousStatus);
        }

        $message = 'Claim updated.';
        if ($shouldNotify) {
            $message = 'Claim updated and the customer has been notified.';
        } elseif ($statusChanged) {
            $message = 'Claim updated without notifying the customer.';
        }

        return back()->with('success', $message);
    }
}
