<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\SettingsService;
use App\Support\LegalContentDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalContentController extends Controller
{
    public function __construct(protected SettingsService $settingsService) {}

    public function edit(Request $request): View
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $settings = $this->settingsService->all();

        return view('admin.legal.edit', [
            'privacyContent' => $settings['privacy_policy_content'] ?? LegalContentDefaults::privacyPolicy(),
            'termsContent' => $settings['warranty_terms_content'] ?? LegalContentDefaults::warrantyTerms(),
            'privacyUrl' => $settings['privacy_policy_url'] ?? url('/privacy-policy'),
            'termsUrl' => $settings['warranty_terms_url'] ?? url('/warranty-terms'),
            'privacyUpdatedAt' => SystemSetting::query()->where('key', 'privacy_policy_content')->value('updated_at'),
            'termsUpdatedAt' => SystemSetting::query()->where('key', 'warranty_terms_content')->value('updated_at'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $data = $request->validate([
            'privacy_policy_url' => ['nullable', 'url', 'max:255'],
            'warranty_terms_url' => ['nullable', 'url', 'max:255'],
            'privacy_policy_content' => ['required', 'string', 'max:100000'],
            'warranty_terms_content' => ['required', 'string', 'max:100000'],
        ]);

        foreach ($data as $key => $value) {
            $this->settingsService->set($key, $value ?? '', 'privacy', 'string');
        }

        return back()->with('success', 'Legal pages updated. Changes are live on the public site.');
    }
}
