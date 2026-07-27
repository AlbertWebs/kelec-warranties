<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('audit_logs.view'), 403);

        $logs = AuditLog::with('user')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where('action', 'like', "%{$term}%")
                    ->orWhere('entity_type', 'like', "%{$term}%");
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }
}
