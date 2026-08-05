@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
@php
    $s = fn (string $key, mixed $default = null) => old($key, $settings[$key] ?? $default);
    $odooOn = (bool) $s('odoo_enabled', false);
    $odooMock = (bool) $s('odoo_mock_mode', true);
    $smsOn = (bool) $s('sms_enabled', false);
    $odooConfigured = filled($s('odoo_base_url')) && filled($s('odoo_database')) && filled($s('odoo_username'));
@endphp

<div
    class="mx-auto max-w-5xl"
    x-data="{
        tab: @js(request('tab', 'general')),
        showOdooKey: false,
        showSmsKey: false,
        tabs: ['general', 'warranty', 'privacy', 'odoo', 'sms', 'email']
    }"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand">Configuration</p>
            <h1 class="mt-1 text-2xl font-bold text-brand-ink sm:text-3xl">System settings</h1>
            <p class="mt-1.5 max-w-xl text-sm text-slate-500">
                Company details, warranty rules, and integrations that power the portal.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span @class([
                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $odooOn && ! $odooMock,
                'bg-amber-50 text-amber-800 ring-amber-600/20' => $odooOn && $odooMock,
                'bg-slate-100 text-slate-600 ring-slate-500/20' => ! $odooOn,
            ])>
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                Odoo {{ $odooOn ? ($odooMock ? 'mock' : 'live') : 'off' }}
            </span>
            <span @class([
                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $smsOn,
                'bg-slate-100 text-slate-600 ring-slate-500/20' => ! $smsOn,
            ])>
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                SMS {{ $smsOn ? 'on' : 'off' }}
            </span>
        </div>
    </div>

    <div class="mb-5 overflow-x-auto">
        <nav class="inline-flex min-w-full gap-1 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm sm:min-w-0" aria-label="Settings sections">
            @foreach ([
                'general' => 'General',
                'warranty' => 'Warranty',
                'privacy' => 'Privacy',
                'odoo' => 'Odoo',
                'sms' => 'SMS',
                'email' => 'Email',
            ] as $id => $label)
                <button
                    type="button"
                    @click="tab = '{{ $id }}'"
                    :class="tab === '{{ $id }}'
                        ? 'bg-brand-navy text-white shadow-sm'
                        : 'text-slate-600 hover:bg-slate-50 hover:text-brand-ink'"
                    class="whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-semibold transition"
                >{{ $label }}</button>
            @endforeach
        </nav>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="pb-24" x-show="tab !== 'sms'">
        @csrf
        @method('PUT')

        {{-- General --}}
        <section x-show="tab === 'general'" x-cloak class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-navy/5 text-brand-navy">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-brand-ink">Company &amp; branding</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Shown on certificates, emails, and the public portal.</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="auth-field sm:col-span-2">
                        <label class="auth-label" for="company_name">Company name</label>
                        <input id="company_name" name="company_name" value="{{ $s('company_name', 'K-Elec') }}" class="auth-input" required>
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="support_phone">Support phone</label>
                        <input id="support_phone" name="support_phone" value="{{ $s('support_phone') }}" class="auth-input" placeholder="e.g. 0700 000 000">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="support_email">Support email</label>
                        <input id="support_email" name="support_email" type="email" value="{{ $s('support_email') }}" class="auth-input" placeholder="support@example.com">
                    </div>
                    <div class="auth-field sm:col-span-2">
                        <label class="auth-label" for="application_url">Application URL</label>
                        <input id="application_url" name="application_url" type="url" value="{{ $s('application_url', url('/')) }}" class="auth-input" placeholder="https://…">
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-base font-semibold text-brand-ink">Locale</h2>
                <p class="mt-0.5 text-sm text-slate-500">Timezone and date presentation for admin screens.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="auth-field">
                        <label class="auth-label" for="default_timezone">Timezone</label>
                        <input id="default_timezone" name="default_timezone" value="{{ $s('default_timezone', 'Africa/Nairobi') }}" class="auth-input" required>
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="default_date_format">Date format</label>
                        <input id="default_date_format" name="default_date_format" value="{{ $s('default_date_format', 'd M Y') }}" class="auth-input" required>
                        <p class="mt-1 text-xs text-slate-400">PHP date format, e.g. <code class="rounded bg-slate-100 px-1">d M Y</code></p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Warranty --}}
        <section x-show="tab === 'warranty'" x-cloak class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-brand-ink">Warranty defaults</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Rules applied when products do not override them.</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="auth-field">
                        <label class="auth-label" for="default_warranty_months">Default warranty (months)</label>
                        <input id="default_warranty_months" type="number" min="1" max="120" name="default_warranty_months" value="{{ $s('default_warranty_months', 12) }}" class="auth-input" required>
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="registration_grace_days">Registration grace (days)</label>
                        <input id="registration_grace_days" type="number" min="0" max="365" name="registration_grace_days" value="{{ $s('registration_grace_days', 30) }}" class="auth-input" required>
                    </div>
                    <div class="auth-field sm:col-span-2">
                        <label class="auth-label" for="warranty_reference_prefix">Reference prefix</label>
                        <input id="warranty_reference_prefix" name="warranty_reference_prefix" value="{{ $s('warranty_reference_prefix', 'KEL-WTY') }}" class="auth-input font-mono" required>
                    </div>
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:col-span-2">
                        <input type="checkbox" name="allow_manual_verification" value="1" @checked($s('allow_manual_verification', true)) class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
                        <span>
                            <span class="block text-sm font-semibold text-brand-ink">Allow manual verification</span>
                            <span class="mt-0.5 block text-sm text-slate-500">Staff can approve registrations that fail automatic Odoo checks.</span>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        {{-- Privacy --}}
        <section x-show="tab === 'privacy'" x-cloak class="space-y-4">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-brand-navy to-brand-ink p-5 text-white shadow-sm sm:p-6">
                <h2 class="text-base font-semibold">Legal page editor</h2>
                <p class="mt-1 max-w-lg text-sm text-white/70">Privacy Policy and Warranty Terms are managed in a dedicated Markdown editor with live public pages.</p>
                <a href="{{ route('admin.legal.edit') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-brand-ink transition hover:bg-brand-soft">
                    Open legal pages
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-base font-semibold text-brand-ink">Canonical URLs</h2>
                <p class="mt-0.5 text-sm text-slate-500">Optional external URLs if content is hosted elsewhere.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="auth-field">
                        <label class="auth-label" for="privacy_policy_url">Privacy policy URL</label>
                        <input id="privacy_policy_url" type="url" name="privacy_policy_url" value="{{ $s('privacy_policy_url') }}" class="auth-input" placeholder="https://…">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="warranty_terms_url">Warranty terms URL</label>
                        <input id="warranty_terms_url" type="url" name="warranty_terms_url" value="{{ $s('warranty_terms_url') }}" class="auth-input" placeholder="https://…">
                    </div>
                </div>
            </div>
        </section>

        {{-- Odoo --}}
        <section x-show="tab === 'odoo'" x-cloak class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-brand-ink">Odoo integration</h2>
                            <p class="mt-0.5 text-sm text-slate-500">POS sales sync, serial validation, and customer lookup.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.odoo.index') }}" class="text-sm font-semibold text-brand hover:underline">Open sync console</a>
                </div>

                @if ($odooOn && ! $odooConfigured)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900">
                        Credentials look incomplete. Fill base URL, database, and username before going live.
                    </div>
                @endif

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <input type="checkbox" name="odoo_enabled" value="1" @checked($odooOn) class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
                        <span>
                            <span class="block text-sm font-semibold text-brand-ink">Enable Odoo</span>
                            <span class="mt-0.5 block text-sm text-slate-500">Allow the portal to call Odoo APIs.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <input type="checkbox" name="odoo_mock_mode" value="1" @checked($odooMock) class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
                        <span>
                            <span class="block text-sm font-semibold text-brand-ink">Mock mode</span>
                            <span class="mt-0.5 block text-sm text-slate-500">Use simulated responses while testing.</span>
                        </span>
                    </label>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="auth-field sm:col-span-2">
                        <label class="auth-label" for="odoo_base_url">Base URL</label>
                        <input id="odoo_base_url" type="url" name="odoo_base_url" value="{{ $s('odoo_base_url') }}" class="auth-input" placeholder="https://odoo.example.com">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="odoo_database">Database</label>
                        <input id="odoo_database" name="odoo_database" value="{{ $s('odoo_database') }}" class="auth-input">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="odoo_username">Username</label>
                        <input id="odoo_username" name="odoo_username" value="{{ $s('odoo_username') }}" class="auth-input">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="odoo_api_key">API key</label>
                        <div class="relative">
                            <input id="odoo_api_key" :type="showOdooKey ? 'text' : 'password'" name="odoo_api_key" value="" class="auth-input pr-10" placeholder="Leave blank to keep current" autocomplete="new-password">
                            <button type="button" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-brand-ink" @click="showOdooKey = !showOdooKey" :aria-label="showOdooKey ? 'Hide key' : 'Show key'">
                                <svg x-show="!showOdooKey" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="showOdooKey" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="odoo_timeout">Timeout (seconds)</label>
                        <input id="odoo_timeout" type="number" min="5" max="120" name="odoo_timeout" value="{{ $s('odoo_timeout', 15) }}" class="auth-input">
                    </div>
                </div>
            </div>
        </section>

        {{-- Email --}}
        <section x-show="tab === 'email'" x-cloak class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-navy/5 text-brand-navy">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-brand-ink">Outbound email</h2>
                        <p class="mt-0.5 text-sm text-slate-500">From address used for warranty and claim notices.</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="auth-field">
                        <label class="auth-label" for="mail_from_address">From address</label>
                        <input id="mail_from_address" type="email" name="mail_from_address" value="{{ $s('mail_from_address') }}" class="auth-input" placeholder="noreply@example.com">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="mail_from_name">From name</label>
                        <input id="mail_from_name" name="mail_from_name" value="{{ $s('mail_from_name', 'K-Elec Warranties') }}" class="auth-input">
                    </div>
                </div>
                <div class="mt-5 rounded-lg border border-dashed border-slate-200 bg-slate-50/50 px-4 py-3 text-sm text-slate-600">
                    SMTP host, port, username, and password are managed via environment variables (e.g. AWS SES).
                </div>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h3 class="text-sm font-semibold text-brand-ink">Send test email</h3>
                    <p class="mt-1 text-sm text-slate-500">Dispatch a test message using the current mail configuration.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="auth-field sm:col-span-2">
                            <label class="auth-label" for="test_email_to">Recipient</label>
                            <input id="test_email_to" form="settings-test-email" type="email" name="to" required value="{{ old('to', auth()->user()->email) }}" class="auth-input" placeholder="you@example.com">
                        </div>
                        <div class="auth-field sm:col-span-2">
                            <label class="auth-label" for="test_email_subject">Subject</label>
                            <input id="test_email_subject" form="settings-test-email" type="text" name="subject" value="{{ old('subject', 'K-Elec warranty portal — test email') }}" class="auth-input">
                        </div>
                        <div class="auth-field sm:col-span-2">
                            <label class="auth-label" for="test_email_body">Message</label>
                            <textarea id="test_email_body" form="settings-test-email" name="body" rows="4" class="auth-input">{{ old('body', "This is a test email from the K-Elec warranty portal.\n\nIf you received this, outbound email is working.") }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button form="settings-test-email" type="submit" class="rounded-lg bg-brand-navy px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">
                            Send test email
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- Sticky save (offset past admin sidebar on desktop) --}}
        <div class="pointer-events-none fixed inset-x-0 bottom-0 z-20 border-t border-slate-200/80 bg-white/90 backdrop-blur-md md:left-72">
            <div class="pointer-events-auto mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 md:px-6">
                <p class="hidden text-sm text-slate-500 sm:block">Changes apply immediately after save.</p>
                <div class="flex w-full items-center justify-end gap-2 sm:w-auto">
                    <button type="button" @click="tab = tabs[(tabs.indexOf(tab) + tabs.length - 1) % tabs.length]" class="rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        Previous
                    </button>
                    <button type="button" @click="tab = tabs[(tabs.indexOf(tab) + 1) % tabs.length]" class="rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        Next
                    </button>
                    <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark">
                        Save settings
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- SMS (managed in dedicated admin segment) --}}
    <section x-show="tab === 'sms'" x-cloak class="space-y-4 pb-24">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-semibold text-brand-ink">SMS moved</h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        AdvantaSMS credentials, balance, logs, and test sends now live under Operations → SMS.
                    </p>
                </div>
            </div>
            <div class="mt-5">
                <a href="{{ route('admin.sms.index', ['tab' => 'settings']) }}" class="inline-flex rounded-lg bg-brand-navy px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">
                    Open SMS settings
                </a>
            </div>
        </div>
    </section>

    <form id="settings-test-email" method="POST" action="{{ route('admin.settings.test-email') }}" class="hidden">
        @csrf
    </form>
</div>
@endsection
