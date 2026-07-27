@extends('layouts.admin')

@section('title', 'Danger zone')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-red-600">Danger zone</p>
        <h1 class="mt-1 text-2xl font-bold text-brand-ink">Demo &amp; test data tools</h1>
        <p class="mt-1 text-sm text-gray-600">
            Seed sample records to review every admin screen, or wipe transactional test data.
            Staff accounts, roles, permissions, settings, and the core catalog are preserved on wipe.
        </p>
    </div>

    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-brand-ink">Seed demo data</h2>
            <p class="mt-1 text-sm text-gray-600">
                Creates sample customers, warranties (multiple statuses), claims, notification logs,
                Odoo sync logs, integration failures, and audit logs so every list/detail screen has content.
            </p>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-gray-600">
                <li>Portal login: <code class="rounded bg-gray-100 px-1">customer@kelec.test</code> / <code class="rounded bg-gray-100 px-1">password</code></li>
                <li>Safe to run more than once (upserts demo references)</li>
            </ul>
            <form method="POST" action="{{ route('admin.demo-data.seed') }}" class="mt-5">
                @csrf
                <button type="submit" class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-ink">
                    Seed demo data
                </button>
            </form>
        </section>

        <section class="rounded-xl border border-red-200 bg-red-50/40 p-5 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-red-700">Delete all test data</h2>
            <p class="mt-1 text-sm text-red-900/80">
                Permanently deletes customers, warranties, claims, notes, notification logs, Odoo logs,
                integration failures, and audit logs. This cannot be undone.
            </p>
            <form method="POST" action="{{ route('admin.demo-data.wipe') }}" class="mt-5 space-y-3">
                @csrf
                <div class="auth-field">
                    <label for="confirm" class="auth-label">Type DELETE to confirm</label>
                    <input id="confirm" name="confirm" type="text" class="auth-input" placeholder="DELETE" required autocomplete="off">
                    @error('confirm')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="auth-field">
                    <label for="password" class="auth-label">Your admin password</label>
                    <input id="password" name="password" type="password" class="auth-input" required autocomplete="current-password" placeholder="Current password">
                    @error('password')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                    Wipe test data
                </button>
            </form>
        </section>
    </div>
</div>
@endsection
