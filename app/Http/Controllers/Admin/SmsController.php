<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Services\SettingsService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService,
        protected SmsService $smsService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('sms.view'), 403);

        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['overview', 'logs', 'settings'], true)) {
            $tab = 'overview';
        }

        $balance = null;
        $balanceError = null;
        if ($tab === 'overview') {
            $result = $this->smsService->getBalance();
            if ($result['ok']) {
                $balance = $result['balance'] ?? null;
            } else {
                $balanceError = $result['response'] ?? 'Unable to fetch balance';
            }
        }

        $logs = null;
        if ($tab === 'logs') {
            $logs = SmsLog::query()
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('q'), function ($q) use ($request) {
                    $term = '%'.$request->string('q').'%';
                    $q->where(function ($inner) use ($term) {
                        $inner->where('mobile', 'like', $term)
                            ->orWhere('message', 'like', $term)
                            ->orWhere('provider_message_id', 'like', $term)
                            ->orWhere('context', 'like', $term);
                    });
                })
                ->latest()
                ->paginate(25)
                ->withQueryString();
        }

        return view('admin.sms.index', [
            'tab' => $tab,
            'settings' => $this->settingsService->all(),
            'smsEnabled' => (bool) $this->settingsService->get('sms_enabled', false),
            'smsConfigured' => $this->smsService->isConfigured(),
            'balance' => $balance,
            'balanceError' => $balanceError,
            'logs' => $logs,
            'stats' => [
                'sent' => SmsLog::where('status', 'sent')->count(),
                'failed' => SmsLog::where('status', 'failed')->count(),
                'mock' => SmsLog::where('status', 'mock')->count(),
                'total' => SmsLog::count(),
            ],
            'responseCodes' => SmsService::RESPONSE_CODES,
            'baseUrl' => (string) $this->settingsService->get('sms_base_url', SmsService::BASE_URL),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('sms.manage'), 403);

        $data = $request->validate([
            'sms_enabled' => ['sometimes', 'boolean'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_partner_id' => ['nullable', 'string', 'max:100'],
            'sms_sender_id' => ['nullable', 'string', 'max:50'],
            'sms_base_url' => ['nullable', 'url', 'max:255'],
            'sms_timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
        ]);

        $data['sms_enabled'] = $request->boolean('sms_enabled');

        foreach ($data as $key => $value) {
            $type = match ($key) {
                'sms_enabled' => 'boolean',
                'sms_timeout' => 'integer',
                default => 'string',
            };
            $encrypt = $key === 'sms_api_key';

            if ($encrypt && ($value === null || $value === '')) {
                continue;
            }

            $this->settingsService->set($key, $value ?? '', 'sms', $type, $encrypt);
        }

        return redirect()
            ->route('admin.sms.index', ['tab' => 'settings'])
            ->with('success', 'SMS API settings saved.');
    }

    public function refreshBalance(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('sms.view'), 403);

        $result = $this->smsService->getBalance();

        return redirect()
            ->route('admin.sms.index', ['tab' => 'overview'])
            ->with(
                $result['ok'] ? 'success' : 'error',
                $result['ok']
                    ? 'Account balance: '.($result['balance'] ?? 'unknown')
                    : ('Balance check failed: '.($result['response'] ?? 'Unknown error'))
            );
    }

    public function sendTest(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('sms.manage'), 403);

        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:480'],
        ]);

        $result = $this->smsService->send($data['mobile'], $data['message'], 'admin_test');

        return redirect()
            ->route('admin.sms.index', ['tab' => 'overview'])
            ->with(
                $result['ok'] ? 'success' : 'error',
                $result['ok']
                    ? 'Test SMS sent successfully'.(isset($result['message_id']) && $result['message_id'] ? ' (ID '.$result['message_id'].')' : '').'.'
                    : ('Test SMS failed: '.($result['response_description'] ?? $result['response'] ?? 'Unknown error'))
            );
    }

    public function deliveryReport(Request $request, SmsLog $smsLog): RedirectResponse
    {
        abort_unless($request->user()->can('sms.view'), 403);

        if (! $smsLog->provider_message_id) {
            return back()->with('error', 'This SMS has no provider message ID for delivery reports.');
        }

        $result = $this->smsService->getDeliveryReport($smsLog->provider_message_id);

        return redirect()
            ->route('admin.sms.index', ['tab' => 'logs'])
            ->with(
                $result['ok'] ? 'success' : 'error',
                $result['ok']
                    ? 'Delivery report: '.($result['response'] ?? 'OK')
                    : ('Delivery report failed: '.($result['response'] ?? 'Unknown error'))
            );
    }
}
