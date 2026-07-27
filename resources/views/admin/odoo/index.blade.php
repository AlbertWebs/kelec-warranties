@extends('layouts.admin')

@section('title', 'Odoo Sync')

@section('content')
<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-brand-ink">Odoo Sync</h1>
        <p class="mt-1 text-sm text-slate-500">Connection status, logs, and retries</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.odoo.test') }}">
            @csrf
            <button class="rounded-lg bg-brand-navy px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Test connection</button>
        </form>
        <form method="POST" action="{{ route('admin.odoo.retry') }}">
            @csrf
            <button class="rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-slate-50">Retry failures</button>
        </form>
    </div>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Last success</div>
        <div class="mt-1 text-lg font-semibold text-brand-ink">{{ optional($lastSuccess?->created_at)->format('d M Y H:i') ?? 'Never' }}</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pending failures</div>
        <div class="mt-1 text-lg font-semibold text-brand-ink">{{ $pendingFailures }}</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Mode</div>
        <div class="mt-1 text-lg font-semibold text-brand-ink">
            @if ($odooMockMode)
                Mock mode
            @elseif ($odooEnabled)
                Live
            @else
                Disabled
            @endif
        </div>
        <div class="mt-1 text-xs text-slate-500">
            @if (! $odooConfigured)
                Credentials incomplete — configure in Settings
            @elseif ($odooMockMode)
                Syncs use mock data until mock mode is turned off
            @elseif (! $odooEnabled)
                Integration is off — enable in Settings
            @else
                Live Odoo requests enabled
            @endif
        </div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <section>
        <h2 class="mb-3 text-base font-semibold text-brand-ink">Integration logs</h2>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">When</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr class="transition hover:bg-brand-soft/80">
                                <td class="px-4 py-3.5 align-top">{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $log->action }}</td>
                                <td class="px-4 py-3.5 align-top">
                                    <span @class([
                                        'inline-flex rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                        'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $log->status === 'success',
                                        'bg-red-50 text-red-700 ring-red-600/20' => $log->status === 'failed',
                                        'bg-slate-100 text-slate-600 ring-slate-500/20' => ! in_array($log->status, ['success', 'failed'], true),
                                    ])>{{ ucfirst($log->status) }}</span>
                                    @if ($log->error_message)
                                        <p class="mt-0.5 text-xs text-red-600">{{ Str::limit($log->error_message, 80) }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-16 text-center text-sm text-slate-500">No sync logs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($logs->hasPages())
            <div class="mt-3">{{ $logs->links() }}</div>
        @endif
    </section>

    <section>
        <h2 class="mb-3 text-base font-semibold text-brand-ink">Failures</h2>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Retries</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($failures as $failure)
                            <tr class="transition hover:bg-brand-soft/80">
                                <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $failure->action }}</td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20">{{ ucfirst($failure->status) }}</span>
                                </td>
                                <td class="px-4 py-3.5 align-top">{{ $failure->retry_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-16 text-center text-sm text-slate-500">No failures recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
