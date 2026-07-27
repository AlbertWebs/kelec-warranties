<?php

namespace App\Http\Controllers\Public;

use App\Enums\RegistrationSource;
use App\Enums\WarrantyStatus;
use App\Http\Controllers\Controller;
use App\Models\PublicAccessToken;
use App\Services\AuditLogger;
use App\Services\NotificationDispatcher;
use App\Services\PhoneNumberService;
use App\Services\WarrantyStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CompleteRegistrationController extends Controller
{
    public function show(string $token): View
    {
        $access = PublicAccessToken::query()
            ->where('token', $token)
            ->where('type', 'complete_registration')
            ->with(['customer', 'warranty.product'])
            ->firstOrFail();

        return view('public.complete.show', [
            'access' => $access,
            'valid' => $access->isValid(),
            'warranty' => $access->warranty,
        ]);
    }

    public function store(
        Request $request,
        string $token,
        PhoneNumberService $phoneNumberService,
        WarrantyStatusService $statusService,
        NotificationDispatcher $notifications,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $access = PublicAccessToken::query()
            ->where('token', $token)
            ->where('type', 'complete_registration')
            ->with(['customer', 'warranty'])
            ->firstOrFail();

        abort_unless($access->isValid(), 410, 'This completion link has expired.');

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'county' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $customer = $access->customer;
        $warranty = $access->warranty;
        abort_if(! $customer || ! $warranty, 404);

        $customer->update([
            'full_name' => $data['full_name'],
            'mobile_number' => $data['mobile_number'],
            'mobile_normalized' => $phoneNumberService->normalize($data['mobile_number']),
            'email' => $data['email'] ?? null,
            'county' => $data['county'] ?? null,
            'town' => $data['town'] ?? null,
            'marketing_consent' => $request->boolean('marketing_consent'),
            'marketing_consent_at' => $request->boolean('marketing_consent') ? now() : $customer->marketing_consent_at,
        ]);

        $start = $warranty->purchase_date ?? now();
        $duration = $warranty->warranty_duration_months ?? 12;

        $warranty->update([
            'marketing_consent' => $request->boolean('marketing_consent'),
            'registration_source' => RegistrationSource::CustomerCompletion,
            'requires_manual_verification' => false,
            'customer_notes' => null,
            'warranty_start_date' => $start,
            'warranty_expiry_date' => Carbon::parse($start)->addMonthsNoOverflow($duration),
            'approved_at' => now(),
        ]);

        if ($warranty->status !== WarrantyStatus::Active) {
            $statusService->transition($warranty, WarrantyStatus::Active, null, 'Customer completed POS registration details');
        }

        $access->update(['used_at' => now()]);
        $notifications->sendWarrantyNotification($warranty->fresh(['customer', 'product']), 'pos_warranty_registered', false);
        $auditLogger->log('customer_information_changed', $warranty, null, ['source' => 'complete_registration']);

        return redirect()
            ->route('register-warranty.success', $warranty->reference)
            ->with('success', 'Thank you. Your warranty details have been completed.');
    }
}
