<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\SettingsService;
use App\Support\LegalContentDefaults;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContentPageController extends Controller
{
    public function privacy(SettingsService $settings): View
    {
        $content = (string) $settings->get('privacy_policy_content', LegalContentDefaults::privacyPolicy());

        return view('public.pages.privacy', [
            'title' => 'Privacy Policy',
            'intro' => 'How K-Elec collects, uses, and protects personal data for warranty registration and support.',
            'contentHtml' => $this->toHtml($content),
            'canonicalUrl' => $settings->get('privacy_policy_url'),
            'updatedAt' => SystemSetting::query()->where('key', 'privacy_policy_content')->value('updated_at'),
            'supportPhone' => $settings->get('support_phone'),
            'supportEmail' => $settings->get('support_email'),
        ]);
    }

    public function terms(SettingsService $settings): View
    {
        $content = (string) $settings->get('warranty_terms_content', LegalContentDefaults::warrantyTerms());

        return view('public.pages.terms', [
            'title' => 'Warranty Terms',
            'intro' => 'Coverage, registration rules, exclusions, and how to raise a warranty claim with K-Elec.',
            'contentHtml' => $this->toHtml($content),
            'canonicalUrl' => $settings->get('warranty_terms_url'),
            'updatedAt' => SystemSetting::query()->where('key', 'warranty_terms_content')->value('updated_at'),
            'supportPhone' => $settings->get('support_phone'),
            'supportEmail' => $settings->get('support_email'),
        ]);
    }

    protected function toHtml(string $content): string
    {
        $trimmed = trim($content);

        if ($trimmed === '') {
            return '';
        }

        // Plain legacy text without markdown headings still renders cleanly.
        if (! preg_match('/^#{1,6}\s|^\*\*|\[.+\]\(.+\)/m', $trimmed) && ! str_contains($trimmed, "\n\n")) {
            return nl2br(e($trimmed));
        }

        if (! preg_match('/^#{1,6}\s/m', $trimmed) && ! str_contains($trimmed, '**') && ! str_contains($trimmed, '[')) {
            return '<p>'.implode('</p><p>', array_map('e', preg_split("/\n{2,}/", $trimmed) ?: [])).'</p>';
        }

        return Str::markdown($trimmed, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
