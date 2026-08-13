<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ClaimStatus;
use App\Enums\WarrantyStatus;
use App\Http\Controllers\Controller;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantyClaimPhoto;
use App\Services\ClaimPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimController extends Controller
{
    public function __construct(
        protected ClaimPhotoService $claimPhotoService,
    ) {}

    public function index(Request $request): View
    {
        $claims = $request->user('customer')
            ->claims()
            ->with('warranty')
            ->latest()
            ->paginate(15);

        return view('customer.claims.index', compact('claims'));
    }

    public function create(Request $request): View
    {
        $warranties = $request->user('customer')
            ->claimableWarranties()
            ->orderByDesc('registration_date')
            ->get();

        return view('customer.claims.create', compact('warranties'));
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        $validated = $request->validate(array_merge([
            'warranty_id' => ['required', 'integer', 'exists:warranties,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ], ClaimPhotoService::validationRules()));

        $warranty = Warranty::query()->findOrFail($validated['warranty_id']);

        if ($warranty->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'warranty_id' => 'You can only create claims against your own warranties.',
            ]);
        }

        if ($warranty->status !== WarrantyStatus::Active) {
            throw ValidationException::withMessages([
                'warranty_id' => 'Claims can only be created against active warranties.',
            ]);
        }

        $claim = WarrantyClaim::query()->create([
            'reference' => WarrantyClaim::generateReference(),
            'customer_id' => $customer->id,
            'warranty_id' => $warranty->id,
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'customer_notes' => $validated['customer_notes'] ?? null,
            'status' => ClaimStatus::Submitted,
        ]);

        $this->claimPhotoService->storeMany($claim, $request->file('photos'));

        return redirect()
            ->route('customer.claims.show', $claim)
            ->with('success', 'Claim '.$claim->reference.' submitted successfully.');
    }

    public function show(Request $request, WarrantyClaim $claim): View
    {
        abort_unless($claim->customer_id === $request->user('customer')->id, 404);

        $claim->load(['warranty', 'photos']);

        return view('customer.claims.show', compact('claim'));
    }

    public function photo(Request $request, WarrantyClaim $claim, WarrantyClaimPhoto $photo): StreamedResponse
    {
        abort_unless($claim->customer_id === $request->user('customer')->id, 404);
        abort_unless($photo->warranty_claim_id === $claim->id, 404);
        abort_unless($photo->existsOnDisk(), 404);

        return Storage::disk($photo->disk)->response($photo->path, $photo->original_name, [
            'Content-Type' => $photo->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
