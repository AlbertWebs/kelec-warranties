<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OdooProductSyncFailure;
use App\Models\OdooProductSyncRun;
use App\Services\ProductSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OdooProductSyncController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('odoo.view'), 403);

        $runs = OdooProductSyncRun::query()->with('starter')->latest()->paginate(15);
        $latest = OdooProductSyncRun::query()->latest()->first();
        $lastCompleted = OdooProductSyncRun::query()
            ->whereIn('status', ['completed', 'completed_with_errors'])
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->first();

        $stats = [
            'last_sync_at' => $lastCompleted?->completed_at,
            'imported' => (int) OdooProductSyncRun::sum('created_records'),
            'updated' => (int) OdooProductSyncRun::sum('updated_records'),
            'failed' => (int) OdooProductSyncFailure::where('status', 'pending')->count(),
            'status' => $latest?->status ?? 'never',
        ];

        $failures = OdooProductSyncFailure::query()
            ->where('status', 'pending')
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.odoo.products', compact('runs', 'stats', 'failures', 'latest'));
    }

    public function sync(Request $request, ProductSyncService $productSyncService): RedirectResponse
    {
        abort_unless($request->user()->can('odoo.manage'), 403);

        $data = $request->validate([
            'sync_type' => ['required', 'in:full,incremental'],
            'confirm_full' => ['nullable', 'in:yes'],
        ]);

        if ($data['sync_type'] === 'full' && ($data['confirm_full'] ?? null) !== 'yes') {
            return back()->with('error', 'Please confirm full synchronization before starting.');
        }

        $run = $productSyncService->queueSync($data['sync_type'], $request->user()->id);

        return back()->with('success', "Product sync queued ({$run->sync_type}). Run #{$run->id}");
    }

    public function retryFailure(Request $request, ProductSyncService $productSyncService, OdooProductSyncFailure $failure): RedirectResponse
    {
        abort_unless($request->user()->can('odoo.manage'), 403);

        $productSyncService->retryFailure($failure);

        return back()->with('success', 'Sync failure retry processed.');
    }

    public function retryPending(Request $request, ProductSyncService $productSyncService): RedirectResponse
    {
        abort_unless($request->user()->can('odoo.manage'), 403);

        OdooProductSyncFailure::query()
            ->where('status', 'pending')
            ->limit(50)
            ->get()
            ->each(fn (OdooProductSyncFailure $failure) => $productSyncService->retryFailure($failure));

        return back()->with('success', 'Pending failed sync records retried.');
    }
}

