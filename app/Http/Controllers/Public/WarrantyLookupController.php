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
            'serial_number' => old(
                'serial_number',
                $request->query('serial', $request->session()->get('lookup_serial'))
            ),
        ]);
    }

    public function store(WarrantyLookupRequest $request): RedirectResponse|View
    {
        $warranty = $this->queryService->lookup(
            $request->validated('serial_number'),
            $request->validated('mobile_number'),
        );

        if (! $warranty) {
            return back()
                ->withInput()
                ->withErrors(['serial_number' => 'No warranty matched the serial number and mobile provided. Please check and try again.']);
        }

        return view('public.lookup.result', compact('warranty'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $request->validate([
            'serial_number' => ['required', 'string', 'max:100'],
            'mobile_number' => ['required', 'string', 'max:20'],
        ]);

        $warranty = $this->queryService->lookup(
            $request->input('serial_number'),
            $request->input('mobile_number'),
        );

        if (! $warranty) {
            return back()->withErrors(['serial_number' => 'Unable to resend details for the provided information.']);
        }

        $this->notificationDispatcher->resend($warranty, 'warranty_lookup');

        return back()->with('success', 'Warranty details have been resent where contact details are available.');
    }
}
