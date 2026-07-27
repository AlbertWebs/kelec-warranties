@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
@php $hasSearch = filled(request('q')); @endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-brand-ink">Audit Logs</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ number_format($logs->total()) }} {{ Str::plural('event', $logs->total()) }}
            @if ($hasSearch)<span class="text-slate-400">· filtered</span>@endif
        </p>
    </div>
</div>

<form method="GET" class="mb-4">
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative min-w-0 flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                </svg>
                <input name="q" value="{{ request('q') }}" placeholder="Search actions…" class="w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Apply</button>
                @if ($hasSearch)<a href="{{ route('admin.audit-logs.index') }}" class="text-sm font-medium text-brand hover:underline">Clear</a>@endif
            </div>
        </div>
    </div>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Entity</th>
                    <th class="px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $log->action }}</td>
                        <td class="px-4 py-3.5 align-top text-slate-600">{{ class_basename((string) $log->entity_type) }} #{{ $log->entity_id }}</td>
                        <td class="px-4 py-3.5 align-top font-mono text-[13px] text-slate-500">{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-16 text-center text-sm text-slate-500">No audit logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
@endif
@endsection
