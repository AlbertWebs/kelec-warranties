<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\View\View;

class ContentPageController extends Controller
{
    public function privacy(SettingsService $settings): View
    {
        return view('public.pages.privacy', [
            'content' => $settings->get('privacy_policy_content', 'K-Elec Privacy Policy content will appear here.'),
            'url' => $settings->get('privacy_policy_url'),
        ]);
    }

    public function terms(SettingsService $settings): View
    {
        return view('public.pages.terms', [
            'content' => $settings->get('warranty_terms_content', 'K-Elec Warranty Terms content will appear here.'),
            'url' => $settings->get('warranty_terms_url'),
        ]);
    }
}
