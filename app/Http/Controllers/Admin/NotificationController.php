<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, SettingsService $settings): View
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $logsQuery = NotificationLog::query()->with(['warranty', 'customer']);

        return view('admin.notifications.index', [
            'logs' => $logsQuery->latest()->paginate(25),
            'templates' => NotificationTemplate::orderBy('name')->get(),
            'stats' => [
                'sent_today' => NotificationLog::query()
                    ->whereDate('created_at', today())
                    ->where('status', 'sent')
                    ->count(),
                'failed_today' => NotificationLog::query()
                    ->whereDate('created_at', today())
                    ->where('status', 'failed')
                    ->count(),
                'sms_enabled' => (bool) $settings->get('sms_enabled', false),
                'support_phone' => support_phone(),
                'support_email' => support_email(),
            ],
        ]);
    }
}
