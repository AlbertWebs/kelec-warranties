<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        return view('admin.notifications.index', [
            'logs' => NotificationLog::with(['warranty', 'customer'])->latest()->paginate(25),
            'templates' => NotificationTemplate::orderBy('name')->get(),
        ]);
    }
}
