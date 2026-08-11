@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
@php
    $stats = $stats ?? [
        'sent_today' => 0,
        'failed_today' => 0,
        'sms_enabled' => false,
        'support_phone' => support_phone(),
        'support_email' => support_email(),
    ];
@endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand">Messaging</p>
        <h1 class="mt-1 text-2xl font-bold text-brand-ink sm:text-3xl">Notifications</h1>
        <p class="mt-1.5 max-w-2xl text-sm text-slate-500">
            Delivery logs for warranty emails and SMS. Support contact on emails comes from
            <a href="{{ route('admin.settings.edit', ['tab' => 'general']) }}" class="font-semibold text-brand hover:underline">Settings → General</a>.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.settings.edit', ['tab' => 'general']) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-slate-50">
            Edit support contact
        </a>
        <a href="{{ route('admin.settings.edit', ['tab' => 'sms']) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-slate-50">
            SMS settings
        </a>
    </div>
</div>

<div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sent today</p>
        <p class="mt-1 text-2xl font-bold text-brand-ink">{{ number_format($stats['sent_today']) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Failed today</p>
        <p @class(['mt-1 text-2xl font-bold', 'text-red-600' => $stats['failed_today'] > 0, 'text-brand-ink' => $stats['failed_today'] === 0])>
            {{ number_format($stats['failed_today']) }}
        </p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">SMS</p>
        <p class="mt-1">
            <span @class([
                'inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-sm font-semibold ring-1 ring-inset',
                'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $stats['sms_enabled'],
                'bg-slate-100 text-slate-600 ring-slate-500/20' => ! $stats['sms_enabled'],
            ])>
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                {{ $stats['sms_enabled'] ? 'Live' : 'Off' }}
            </span>
        </p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Support line</p>
        <p class="mt-1 text-lg font-semibold text-brand-ink">{{ $stats['support_phone'] }}</p>
        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $stats['support_email'] }}</p>
    </div>
</div>

<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-brand-ink">Templates</h2>
            <p class="mt-0.5 text-sm text-slate-500">Active templates use <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">{{ '{{support_phone}}' }}</code> from settings.</p>
        </div>
        <span class="text-xs font-medium text-slate-400">{{ $templates->count() }} total</span>
    </div>
    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        @forelse ($templates as $template)
            <div class="rounded-lg border border-slate-100 bg-slate-50/70 p-3.5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-brand-ink">{{ $template->name }}</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $template->key }}</p>
                    </div>
                    @if ($template->is_active)
                        <span class="inline-flex shrink-0 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>
                    @else
                        <span class="inline-flex shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">Inactive</span>
                    @endif
                </div>
                <p class="mt-2 text-xs font-medium text-slate-500">{{ $template->channel->label() }}</p>
            </div>
        @empty
            <p class="text-sm text-slate-500 sm:col-span-2">No templates configured.</p>
        @endforelse
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
        <h2 class="text-base font-semibold text-brand-ink">Delivery log</h2>
        <span class="text-xs text-slate-400">Latest {{ $logs->count() }} of {{ number_format($logs->total()) }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Channel</th>
                    <th class="px-4 py-3">Recipient</th>
                    <th class="px-4 py-3">Warranty</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top whitespace-nowrap text-slate-600">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3.5 align-top font-medium capitalize text-brand-ink">{{ str_replace('_', ' ', $log->notification_type) }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $log->channel->label() }}</td>
                        <td class="px-4 py-3.5 align-top">
                            <p class="tabular-nums text-brand-ink">{{ $log->recipient }}</p>
                            @if ($log->customer)
                                <p class="mt-0.5 text-xs text-slate-500">{{ $log->customer->full_name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-top">
                            @if ($log->warranty)
                                <a href="{{ route('admin.warranties.show', $log->warranty) }}" class="font-mono text-xs font-semibold text-brand hover:underline">
                                    {{ $log->warranty->reference }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-top">
                            <span @class([
                                'inline-flex rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                'bg-emerald-50 text-emerald-700 ring-emerald-600/20' => $log->status === 'sent',
                                'bg-red-50 text-red-700 ring-red-600/20' => $log->status === 'failed',
                                'bg-slate-100 text-slate-600 ring-slate-500/20' => ! in_array($log->status, ['sent', 'failed'], true),
                            ])>{{ ucfirst($log->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-16 text-center text-sm text-slate-500">No notification logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
@endif
@endsection
