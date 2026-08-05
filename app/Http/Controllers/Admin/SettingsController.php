<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SettingsTestMail;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $settingsService) {}

    public function edit(Request $request): View
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        return view('admin.settings.edit', [
            'settings' => $this->settingsService->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'application_url' => ['nullable', 'url', 'max:255'],
            'default_timezone' => ['required', 'string', 'max:100'],
            'default_date_format' => ['required', 'string', 'max:50'],
            'default_warranty_months' => ['required', 'integer', 'min:1', 'max:120'],
            'registration_grace_days' => ['required', 'integer', 'min:0', 'max:365'],
            'warranty_reference_prefix' => ['required', 'string', 'max:20'],
            'allow_manual_verification' => ['sometimes', 'boolean'],
            'privacy_policy_url' => ['nullable', 'url', 'max:255'],
            'warranty_terms_url' => ['nullable', 'url', 'max:255'],
            'privacy_policy_content' => ['sometimes', 'nullable', 'string'],
            'warranty_terms_content' => ['sometimes', 'nullable', 'string'],
            'odoo_enabled' => ['sometimes', 'boolean'],
            'odoo_mock_mode' => ['sometimes', 'boolean'],
            'odoo_base_url' => ['nullable', 'url', 'max:255'],
            'odoo_database' => ['nullable', 'string', 'max:150'],
            'odoo_username' => ['nullable', 'string', 'max:150'],
            'odoo_api_key' => ['nullable', 'string', 'max:255'],
            'odoo_timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
            'mail_from_address' => ['nullable', 'email', 'max:150'],
            'mail_from_name' => ['nullable', 'string', 'max:150'],
        ]);

        $bools = [
            'allow_manual_verification',
            'odoo_enabled',
            'odoo_mock_mode',
        ];

        foreach ($bools as $boolKey) {
            $data[$boolKey] = $request->boolean($boolKey);
        }

        foreach ($data as $key => $value) {
            $group = match (true) {
                str_starts_with($key, 'odoo_') => 'odoo',
                str_starts_with($key, 'mail_') => 'email',
                str_contains($key, 'privacy') || str_contains($key, 'warranty_terms') => 'privacy',
                in_array($key, ['default_warranty_months', 'registration_grace_days', 'warranty_reference_prefix', 'allow_manual_verification'], true) => 'warranty',
                default => 'general',
            };

            $type = in_array($key, $bools, true) ? 'boolean' : (in_array($key, ['default_warranty_months', 'registration_grace_days', 'odoo_timeout'], true) ? 'integer' : 'string');
            $encrypt = in_array($key, ['odoo_api_key'], true);

            if ($encrypt && ($value === null || $value === '')) {
                continue;
            }

            $this->settingsService->set($key, $value ?? '', $group, $type, $encrypt);
        }

        return back()->with('success', 'Settings saved.');
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $data = $request->validate([
            'to' => ['required', 'email', 'max:150'],
            'subject' => ['nullable', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $fromAddress = (string) $this->settingsService->get('mail_from_address', config('mail.from.address'));
        $fromName = (string) $this->settingsService->get('mail_from_name', config('mail.from.name'));
        $subject = $data['subject'] ?: 'K-Elec warranty portal — test email';
        $body = $data['body'] ?: "This is a test email from the K-Elec warranty portal.\n\nIf you received this, outbound email is working.\n\nSent at: ".now()->toDateTimeString();

        try {
            $mailable = new SettingsTestMail($subject, $body);

            if ($fromAddress !== '') {
                $mailable->from($fromAddress, $fromName !== '' ? $fromName : null);
            }

            Mail::to($data['to'])->send($mailable);

            return redirect()
                ->route('admin.settings.edit', ['tab' => 'email'])
                ->with('success', 'Test email sent to '.$data['to'].'.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.settings.edit', ['tab' => 'email'])
                ->with('error', 'Test email failed: '.$e->getMessage());
        }
    }
}
