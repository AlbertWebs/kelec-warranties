<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DemoDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemoDataController extends Controller
{
    public function show(Request $request): View
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        return view('admin.demo-data.show');
    }

    public function seed(Request $request, DemoDataService $demoData): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        try {
            $counts = $demoData->seed();
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            "Demo data ready: {$counts['customers']} customers, {$counts['warranties']} warranties, {$counts['claims']} claims, plus notification/odoo/audit logs."
        );
    }

    public function wipe(Request $request, DemoDataService $demoData): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        $request->validate([
            'password' => ['required', 'current_password'],
            'confirm' => ['required', 'in:DELETE'],
        ], [
            'confirm.in' => 'Type DELETE to confirm wiping test data.',
        ]);

        $demoData->wipe();

        return redirect()
            ->route('admin.demo-data.show')
            ->with('success', 'Test data wiped. Staff users, roles, settings, and core catalog were preserved.');
    }
}
