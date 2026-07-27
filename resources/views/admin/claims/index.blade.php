@extends('layouts.admin')

@section('title', 'Claims')

@section('content')
@php
    $hasStatus = filled(request('status'));
@endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-brand-ink">Warranty Claims</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ number_format($claims->total()) }}
            {{ Str::plural('claim', $claims->total()) }}
            @if ($hasStatus)
                <span class="text-slate-400">· filtered</span>
            @endif
        </p>
    </div>
</div>

<form method="GET" class="mb-4">
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <select name="status" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand sm:w-64">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">
                    Apply
                </button>
                @if ($hasStatus)
                    <a href="{{ route('admin.claims.index') }}" class="text-sm font-medium text-brand hover:underline">Clear</a>
                @endif
            </div>
        </div>
    </div>
</form>

<div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:block">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Warranty</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Submitted</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($claims as $claim)
                    <tr class="group transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top">
                            <a href="{{ route('admin.claims.show', $claim) }}" class="font-mono text-[13px] font-semibold text-brand hover:underline">{{ $claim->reference }}</a>
                        </td>
                        <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $claim->customer?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3.5 align-top">
                            @if ($claim->warranty)
                                <a href="{{ route('admin.warranties.show', $claim->warranty) }}" class="font-mono text-[13px] font-semibold text-brand hover:underline">{{ $claim->warranty->reference }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-top">{{ $claim->subject }}</td>
                        <td class="px-4 py-3.5 align-top"><x-admin.status-badge :status="$claim->status" /></td>
                        <td class="px-4 py-3.5 align-top text-slate-600">{{ $claim->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center text-sm text-slate-500">No claims found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="space-y-3 lg:hidden">
    @forelse ($claims as $claim)
        <a href="{{ route('admin.claims.show', $claim) }}" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-brand/30 hover:shadow">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-mono text-sm font-semibold text-brand">{{ $claim->reference }}</p>
                    <p class="mt-1 truncate font-medium text-brand-ink">{{ $claim->customer?->full_name ?? '—' }}</p>
                </div>
                <x-admin.status-badge :status="$claim->status" />
            </div>
            <p class="mt-2 text-sm text-slate-600">{{ $claim->subject }}</p>
        </a>
    @empty
        <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-500">No claims found.</div>
    @endforelse
</div>

@if ($claims->hasPages())
    <div class="mt-4">{{ $claims->links() }}</div>
@endif
@endsection
