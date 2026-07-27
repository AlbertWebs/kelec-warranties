@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-brand-ink">Notifications</h1>
    <p class="mt-1 text-sm text-slate-500">Templates and delivery logs</p>
</div>

<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-base font-semibold text-brand-ink">Templates</h2>
    <ul class="mt-3 divide-y divide-slate-100">
        @forelse ($templates as $template)
            <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm first:pt-0 last:pb-0">
                <div>
                    <p class="font-medium text-brand-ink">{{ $template->name }}</p>
                    <p class="text-xs text-slate-500">{{ $template->key }} · {{ $template->channel->label() }}</p>
                </div>
                @if ($template->is_active)
                    <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>
                @else
                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">Inactive</span>
                @endif
            </li>
        @empty
            <li class="text-sm text-slate-500">No templates configured.</li>
        @endforelse
    </ul>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Channel</th>
                    <th class="px-4 py-3">Recipient</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ str_replace('_', ' ', $log->notification_type) }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $log->channel->label() }}</td>
                        <td class="px-4 py-3.5 align-top tabular-nums">{{ $log->recipient }}</td>
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
                    <tr><td colspan="5" class="px-4 py-16 text-center text-sm text-slate-500">No notification logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
@endif
@endsection
