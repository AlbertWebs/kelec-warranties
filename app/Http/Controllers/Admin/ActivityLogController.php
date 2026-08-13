<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('activity_logs.view'), 403);

        $logs = ActivityLog::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(function ($inner) use ($term) {
                    $inner->where('query', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%")
                        ->orWhere('action', 'like', "%{$term}%")
                        ->orWhere('result_summary', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'types' => [
                'warranty_lookup' => 'Warranty lookup',
                'product_lookup' => 'Product lookup',
                'odoo_fetch' => 'Odoo fetch',
            ],
            'statuses' => ['found', 'not_found', 'success', 'failed', 'error'],
        ]);
    }
}
