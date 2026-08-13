<?php

namespace App\Http\Controllers\Public;

use App\Enums\ClaimStatus;
use App\Enums\WarrantyStatus;
use App\Http\Controllers\Controller;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Services\ClaimPhotoService;
use App\Services\WarrantyQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WarrantyHubController extends Controller
{
    private const SESSION_WARRANTY_ID = 'public_claim.warranty_id';

    public function __construct(
        protected WarrantyQueryService $queryService,
        protected ClaimPhotoService $claimPhotoService,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $tab = $request->query('tab', 'register');

        if ($tab === 'register') {
            return redirect()->route('register-warranty.create');
        }

        if ($tab === 'lookup') {
            return redirect()->route('warranty.lookup');
        }

        if ($tab !== 'claim') {
            return redirect()->route('warranty.hub', ['tab' => 'claim']);
        }

        $warranty = $this->sessionWarranty();
        $submittedClaim = null;

        if ($reference = session('submitted_claim_reference')) {
            $submittedClaim = WarrantyClaim::query()
                ->with(['warranty', 'photos'])
                ->where('reference', $reference)
                ->first();
        }

        return view('public.warranty.hub', [
            'tab' => 'claim',
            'warranty' => $submittedClaim ? null : $warranty,
            'submittedClaim' => $submittedClaim,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:100'],
            'mobile_number' => ['required', 'string', 'max:20'],
        ], [
            'serial_number.required' => 'Enter the product serial number.',
            'mobile_number.required' => 'Enter the mobile number used at registration.',
        ]);

        $warranty = $this->queryService->lookup(
            $validated['serial_number'],
            $validated['mobile_number'],
        );

        if (! $warranty) {
            throw ValidationException::withMessages([
                'serial_number' => 'No warranty matched the serial number and mobile provided. Please check and try again.',
            ]);
        }

        if ($warranty->status !== WarrantyStatus::Active) {
            throw ValidationException::withMessages([
                'serial_number' => 'Claims can only be filed against an active warranty. This warranty is '.$warranty->status->label().'.',
            ]);
        }

        $request->session()->put(self::SESSION_WARRANTY_ID, $warranty->id);

        return redirect()->route('warranty.hub', ['tab' => 'claim']);
    }

    public function store(Request $request): RedirectResponse
    {
        $warranty = $this->sessionWarranty();

        if (! $warranty) {
            return redirect()
                ->route('warranty.hub', ['tab' => 'claim'])
                ->withErrors(['serial_number' => 'Verify your warranty details before filing a claim.']);
        }

        if ($warranty->status !== WarrantyStatus::Active) {
            $request->session()->forget(self::SESSION_WARRANTY_ID);

            return redirect()
                ->route('warranty.hub', ['tab' => 'claim'])
                ->withErrors(['serial_number' => 'Claims can only be filed against an active warranty.']);
        }

        $validated = $request->validate(array_merge([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ], ClaimPhotoService::validationRules()));

        $claim = WarrantyClaim::query()->create([
            'reference' => WarrantyClaim::generateReference(),
            'customer_id' => $warranty->customer_id,
            'warranty_id' => $warranty->id,
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'customer_notes' => $validated['customer_notes'] ?? null,
            'status' => ClaimStatus::Submitted,
        ]);

        $this->claimPhotoService->storeMany($claim, $request->file('photos'));

        $request->session()->forget(self::SESSION_WARRANTY_ID);

        return redirect()
            ->route('warranty.hub', ['tab' => 'claim'])
            ->with('submitted_claim_reference', $claim->reference);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_WARRANTY_ID);

        return redirect()->route('warranty.hub', ['tab' => 'claim']);
    }

    private function sessionWarranty(): ?Warranty
    {
        $warrantyId = session(self::SESSION_WARRANTY_ID);

        if (! $warrantyId) {
            return null;
        }

        return Warranty::query()
            ->with(['customer', 'product'])
            ->find($warrantyId);
    }
}
