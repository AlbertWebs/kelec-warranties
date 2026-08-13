@extends('layouts.admin')

@section('title', 'Activity Logs')

@section('content')
@php
    $hasFilters = filled(request('q')) || filled(request('type')) || filled(request('status'));
@endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-brand-ink">Activity Logs</h1>
        <p class="mt-1 text-sm text-slate-500">
            Warranty lookups, product lookups, and Odoo fetch activity
            · {{ number_format($logs->total()) }} {{ Str::plural('entry', $logs->total()) }}
            @if ($hasFilters)<span class="text-slate-400">· filtered</span>@endif
        </p>
    </div>
</div>

<form method="GET" class="mb-4">
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative min-w-0 flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                </svg>
                <input name="q" value="{{ request('q') }}" placeholder="Search query, reference, action…" class="w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <select name="type" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                <option value="">All types</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Apply</button>
                @if ($hasFilters)<a href="{{ route('admin.activity-logs.index') }}" class="text-sm font-medium text-brand hover:underline">Clear</a>@endif
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
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Query</th>
                    <th class="px-4 py-3">Summary</th>
                    <th class="px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <tr class="transition hover:bg-brand-soft/80">
                        <td class="whitespace-nowrap px-4 py-3.5 align-top text-slate-600">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                        <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $log->typeLabel() }}</td>
                        <td class="px-4 py-3.5 align-top font-mono text-[13px] text-slate-600">{{ $log->action }}</td>
                        <td class="px-4 py-3.5 align-top">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $log->statusBadgeClasses() }}">
                                {{ $log->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 align-top font-mono text-[13px] text-slate-700">
                            {{ $log->query ?: '—' }}
                            @if ($log->reference)
                                <div class="mt-0.5 text-xs text-slate-400">{{ $log->reference }}</div>
                            @endif
                        </td>
                        <td class="max-w-xs px-4 py-3.5 align-top text-slate-600">{{ $log->result_summary ?: '—' }}</td>
                        <td class="px-4 py-3.5 align-top font-mono text-[13px] text-slate-500">{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-16 text-center text-sm text-slate-500">No activity logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
@endif
@endsection
