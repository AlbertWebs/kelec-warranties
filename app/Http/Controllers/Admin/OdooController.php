<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RetryFailedOdooValidation;
use App\Models\IntegrationFailure;
use App\Models\OdooSyncLog;
use App\Services\Odoo\OdooSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OdooController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('odoo.view'), 403);

        return view('admin.odoo.index', [
            'logs' => OdooSyncLog::latest()->paginate(20),
            'failures' => IntegrationFailure::latest()->limit(20)->get(),
            'lastSuccess' => OdooSyncLog::where('status', 'success')->latest()->first(),
            'pendingFailures' => IntegrationFailure::where('status', 'pending')->count(),
        ]);
    }

    public function testConnection(Request $request, OdooSyncService $syncService): RedirectResponse
    {
        abort_unless($request->user()->can('odoo.manage'), 403);

        $result = $syncService->testConnection();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function retryFailures(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('odoo.manage'), 403);

        IntegrationFailure::query()
            ->where('status', 'pending')
            ->limit(50)
            ->get()
            ->each(fn (IntegrationFailure $failure) => RetryFailedOdooValidation::dispatch($failure->id));

        return back()->with('success', 'Failed Odoo validations have been queued for retry.');
    }
}
