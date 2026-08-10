<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\WarrantyLookupRequest;
use App\Services\NotificationDispatcher;
use App\Services\WarrantyQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarrantyLookupController extends Controller
{
    public function __construct(
        protected WarrantyQueryService $queryService,
        protected NotificationDispatcher $notificationDispatcher,
    ) {}

    public function create(Request $request): View
    {
        return view('public.lookup.index', [
            'reference' => old('reference', $request->query('reference')),
            'serial_number' => old('serial_number', $request->query('serial', $request->session()->get('lookup_serial'))),
        ]);
    }

    public function store(WarrantyLookupRequest $request): RedirectResponse|View
    {
        $warranty = $this->queryService->lookup(
            $request->validated('reference'),
            $request->validated('serial_number'),
            $request->validated('mobile_number'),
        );

        if (! $warranty) {
            return back()
                ->withInput()
                ->withErrors(['mobile_number' => 'No warranty matched the details provided. Please check and try again.']);
        }

        return view('public.lookup.result', compact('warranty'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $request->validate([
            'reference' => ['required', 'string'],
            'mobile_number' => ['required', 'string'],
            'channel' => ['nullable', 'in:sms,email,both'],
        ]);

        $warranty = $this->queryService->lookup(
            $request->input('reference'),
            null,
            $request->input('mobile_number'),
        );

        if (! $warranty) {
            return back()->withErrors(['mobile_number' => 'Unable to resend details for the provided information.']);
        }

        $this->notificationDispatcher->resend($warranty, 'warranty_lookup');

        return back()->with('success', 'Warranty details have been resent where contact details are available.');
    }
}
