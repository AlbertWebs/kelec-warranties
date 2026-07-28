@extends('layouts.admin')

@section('title', 'SMS')

@section('content')
@php
    $s = fn (string $key, mixed $default = null) => old($key, $settings[$key] ?? $default);
@endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-brand-ink">SMS</h1>
        <p class="mt-1 text-sm text-slate-500">AdvantaSMS balance, delivery logs, and API configuration</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <span @class([
            'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
            'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $smsEnabled && $smsConfigured,
            'bg-amber-50 text-amber-800 ring-amber-600/20' => $smsEnabled && ! $smsConfigured,
            'bg-slate-100 text-slate-600 ring-slate-500/20' => ! $smsEnabled,
        ])>
            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
            @if (! $smsEnabled)
                Disabled / mock
            @elseif (! $smsConfigured)
                Credentials incomplete
            @else
                Live
            @endif
        </span>
    </div>
</div>

<nav class="mb-6 flex flex-wrap gap-1 border-b border-slate-200">
    @foreach ([
        'overview' => 'Overview',
        'logs' => 'SMS logs',
        'settings' => 'API settings',
    ] as $key => $label)
        <a
            href="{{ route('admin.sms.index', ['tab' => $key]) }}"
            @class([
                'border-b-2 px-3.5 py-2.5 text-sm font-semibold transition',
                'border-brand text-brand-ink' => $tab === $key,
                'border-transparent text-slate-500 hover:text-brand-ink' => $tab !== $key,
            ])
        >{{ $label }}</a>
    @endforeach
</nav>

@if ($tab === 'overview')
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Account balance</div>
            <div class="mt-1 text-lg font-semibold text-brand-ink">
                @if ($balanceError)
                    —
                @else
                    {{ $balance ?? '—' }}
                @endif
            </div>
            @if ($balanceError)
                <p class="mt-1 text-xs text-red-600">{{ $balanceError }}</p>
            @endif
            <form method="POST" action="{{ route('admin.sms.refresh-balance') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-xs font-semibold text-brand hover:underline">Refresh balance</button>
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sent</div>
            <div class="mt-1 text-lg font-semibold text-brand-ink">{{ $stats['sent'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Failed</div>
            <div class="mt-1 text-lg font-semibold text-brand-ink">{{ $stats['failed'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total logs</div>
            <div class="mt-1 text-lg font-semibold text-brand-ink">{{ $stats['total'] }}</div>
            @if ($stats['mock'] > 0)
                <p class="mt-1 text-xs text-slate-500">{{ $stats['mock'] }} mock</p>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-brand-ink">Send test SMS</h2>
            <p class="mt-1 text-sm text-slate-500">Uses the configured AdvantaSMS credentials and shortcode.</p>
            @can('sms.manage')
                <form method="POST" action="{{ route('admin.sms.test') }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="auth-field">
                        <label class="auth-label" for="mobile">Mobile number</label>
                        <input id="mobile" name="mobile" value="{{ old('mobile') }}" class="auth-input" placeholder="2547XXXXXXXX" required>
                        @error('mobile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="message">Message</label>
                        <textarea id="message" name="message" rows="3" class="auth-input" required maxlength="480">{{ old('message', 'K-Elec Warranties test SMS') }}</textarea>
                        @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-brand-navy px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Send test</button>
                </form>
            @else
                <p class="mt-4 text-sm text-slate-500">You need SMS manage permission to send tests.</p>
            @endcan
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-brand-ink">API endpoints</h2>
            <p class="mt-1 text-sm text-slate-500">Advanta Bulk SMS API used by this system.</p>
            <ul class="mt-4 space-y-2 text-sm">
                <li class="flex flex-col gap-0.5 rounded-lg bg-slate-50 px-3 py-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Send SMS</span>
                    <code class="break-all text-brand-ink">{{ rtrim($baseUrl, '/') }}/sendsms/</code>
                </li>
                <li class="flex flex-col gap-0.5 rounded-lg bg-slate-50 px-3 py-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Balance</span>
                    <code class="break-all text-brand-ink">{{ rtrim($baseUrl, '/') }}/getbalance/</code>
                </li>
                <li class="flex flex-col gap-0.5 rounded-lg bg-slate-50 px-3 py-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Delivery report</span>
                    <code class="break-all text-brand-ink">{{ rtrim($baseUrl, '/') }}/getdlr/</code>
                </li>
            </ul>
            <p class="mt-4 text-xs text-slate-500">
                Docs:
                <a href="https://www.advantasms.com/bulksms-api" target="_blank" rel="noopener" class="font-semibold text-brand hover:underline">advantasms.com/bulksms-api</a>
            </p>
        </section>
    </div>
@endif

@if ($tab === 'logs')
    <form method="GET" action="{{ route('admin.sms.index') }}" class="mb-4 flex flex-wrap gap-2">
        <input type="hidden" name="tab" value="logs">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search mobile, message, ID…" class="auth-input max-w-xs">
        <select name="status" class="auth-input max-w-[10rem]">
            <option value="">All statuses</option>
            @foreach (['sent', 'failed', 'mock', 'pending'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-slate-50">Filter</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">Mobile</th>
                        <th class="px-4 py-3">Message</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="transition hover:bg-brand-soft/80">
                            <td class="px-4 py-3.5 align-top whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3.5 align-top tabular-nums">{{ $log->mobile }}</td>
                            <td class="px-4 py-3.5 align-top">
                                <p class="max-w-xs text-brand-ink">{{ \Illuminate\Support\Str::limit($log->message, 80) }}</p>
                                @if ($log->context)
                                    <p class="mt-0.5 text-xs text-slate-500">{{ str_replace('_', ' ', $log->context) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-top">
                                <span @class([
                                    'inline-flex rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                    'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $log->status === 'sent',
                                    'bg-red-50 text-red-700 ring-red-600/20' => $log->status === 'failed',
                                    'bg-amber-50 text-amber-800 ring-amber-600/20' => $log->status === 'mock',
                                    'bg-slate-100 text-slate-600 ring-slate-500/20' => ! in_array($log->status, ['sent', 'failed', 'mock'], true),
                                ])>{{ ucfirst($log->status) }}</span>
                                @if ($log->response_description)
                                    <p class="mt-0.5 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($log->response_description, 60) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-top text-xs text-slate-500">
                                @if ($log->provider_message_id)
                                    <div>ID {{ $log->provider_message_id }}</div>
                                @endif
                                @if ($log->response_code)
                                    <div>Code {{ $log->response_code }}</div>
                                @endif
                                @if ($log->shortcode)
                                    <div>{{ $log->shortcode }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-top text-right">
                                @if ($log->provider_message_id && $smsEnabled && $smsConfigured)
                                    <form method="POST" action="{{ route('admin.sms.delivery-report', $log) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-brand hover:underline">DLR</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-16 text-center text-sm text-slate-500">No SMS logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($logs && $logs->hasPages())
        <div class="mt-4">{{ $logs->links() }}</div>
    @endif
@endif

@if ($tab === 'settings')
    @can('sms.manage')
        <form method="POST" action="{{ route('admin.sms.settings') }}" class="mx-auto max-w-3xl space-y-4" x-data="{ showKey: false }">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-brand-ink">AdvantaSMS credentials</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Get API key and Partner ID from your Advanta account (“GET API KEY &amp; PARTNER ID”).</p>
                    </div>
                </div>

                <label class="mt-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    <input type="checkbox" name="sms_enabled" value="1" @checked((bool) $s('sms_enabled', false)) class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
                    <span>
                        <span class="block text-sm font-semibold text-brand-ink">Enable live SMS</span>
                        <span class="mt-0.5 block text-sm text-slate-500">When off, sends are mocked and logged locally without calling Advanta.</span>
                    </span>
                </label>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="auth-field">
                        <label class="auth-label" for="sms_partner_id">Partner ID</label>
                        <input id="sms_partner_id" name="sms_partner_id" value="{{ $s('sms_partner_id') }}" class="auth-input" autocomplete="off">
                        @error('sms_partner_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="sms_sender_id">Sender ID / Shortcode</label>
                        <input id="sms_sender_id" name="sms_sender_id" value="{{ $s('sms_sender_id') }}" class="auth-input" autocomplete="off">
                        @error('sms_sender_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="auth-field sm:col-span-2">
                        <label class="auth-label" for="sms_api_key">API key</label>
                        <div class="relative">
                            <input id="sms_api_key" :type="showKey ? 'text' : 'password'" name="sms_api_key" value="" class="auth-input pr-10" placeholder="{{ filled($settings['sms_api_key'] ?? null) ? 'Leave blank to keep current' : 'Paste API key' }}" autocomplete="new-password">
                            <button type="button" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-brand-ink" @click="showKey = !showKey">
                                <svg x-show="!showKey" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="showKey" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        @error('sms_api_key') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="auth-field sm:col-span-2">
                        <label class="auth-label" for="sms_base_url">API base URL</label>
                        <input id="sms_base_url" type="url" name="sms_base_url" value="{{ $s('sms_base_url', \App\Services\SmsService::BASE_URL) }}" class="auth-input">
                        @error('sms_base_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="sms_timeout">Timeout (seconds)</label>
                        <input id="sms_timeout" type="number" min="5" max="120" name="sms_timeout" value="{{ $s('sms_timeout', 15) }}" class="auth-input">
                        @error('sms_timeout') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Save SMS settings</button>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-base font-semibold text-brand-ink">Response codes</h2>
                <p class="mt-1 text-sm text-slate-500">Common AdvantaSMS API response codes.</p>
                <div class="mt-4 overflow-hidden rounded-lg border border-slate-100">
                    <table class="min-w-full text-left text-sm">
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($responseCodes as $code => $label)
                                <tr>
                                    <td class="w-20 px-3 py-2 font-mono text-xs font-semibold text-brand-ink">{{ $code }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $label }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
            You need SMS manage permission to edit API settings.
        </div>
    @endcan
@endif
@endsection
