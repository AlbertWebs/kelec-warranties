<?php

namespace App\Http\Controllers\Public;

use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Models\PublicAccessToken;
use App\Models\WarrantyConsent;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingConsentController extends Controller
{
    public function show(string $token): View
    {
        $access = PublicAccessToken::query()
            ->where('token', $token)
            ->where('type', 'marketing_consent')
            ->with('customer')
            ->firstOrFail();

        return view('public.consent.show', [
            'access' => $access,
            'valid' => $access->isValid(),
        ]);
    }

    public function store(Request $request, string $token, AuditLogger $auditLogger): RedirectResponse
    {
        $access = PublicAccessToken::query()
            ->where('token', $token)
            ->where('type', 'marketing_consent')
            ->with('customer')
            ->firstOrFail();

        abort_unless($access->isValid(), 410, 'This consent link has expired.');

        $data = $request->validate([
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $granted = $request->boolean('marketing_consent');
        $customer = $access->customer;
        abort_if(! $customer, 404);

        $customer->update([
            'marketing_consent' => $granted,
            'marketing_consent_at' => $granted ? now() : null,
        ]);

        if ($access->warranty_id) {
            $access->warranty?->update(['marketing_consent' => $granted]);
        }

        WarrantyConsent::create([
            'warranty_id' => $access->warranty_id,
            'customer_id' => $customer->id,
            'consent_type' => ConsentType::Marketing,
            'granted' => $granted,
            'source' => 'post_purchase_link',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'consented_at' => now(),
        ]);

        $access->update(['used_at' => now()]);
        $auditLogger->log('consent_updated', $customer, null, [
            'marketing_consent' => $granted,
            'source' => 'post_purchase_link',
        ]);

        return redirect()
            ->route('consent.show', $token)
            ->with('success', $granted
                ? 'Thank you. You are opted in to marketing communication.'
                : 'Preference saved. You will not receive marketing communication.');
    }
}
