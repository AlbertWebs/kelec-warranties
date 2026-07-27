@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
@php
    $hasSearch = filled(request('q'));
@endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-brand-ink">Customers</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ number_format($customers->total()) }}
            {{ Str::plural('customer', $customers->total()) }}
            @if ($hasSearch)
                <span class="text-slate-400">· filtered</span>
            @endif
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
                <input
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search name, phone, or email…"
                    class="w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-brand focus:ring-brand"
                >
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">
                    Apply
                </button>
                @if ($hasSearch)
                    <a href="{{ route('admin.customers.index') }}" class="text-sm font-medium text-brand hover:underline">Clear</a>
                @endif
            </div>
        </div>
    </div>
</form>

@if ($hasSearch)
    <div class="mb-3 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Active</span>
        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">“{{ request('q') }}”</span>
    </div>
@endif

{{-- Desktop --}}
<div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:block">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Warranties</th>
                    <th class="px-4 py-3">Consent</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <tr class="group transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top">
                            <p class="font-medium text-brand-ink">{{ $customer->full_name }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                @if ($customer->password)
                                    <span class="inline-flex rounded-md bg-sky-50 px-1.5 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-600/20">Portal</span>
                                @endif
                                @if ($customer->possible_duplicate)
                                    <span class="inline-flex rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20">Possible duplicate</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3.5 align-top">
                            <p class="tabular-nums font-medium text-brand-ink">{{ $customer->mobile_number ?? $customer->mobile_normalized }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $customer->email ?: 'No email' }}</p>
                        </td>
                        <td class="px-4 py-3.5 align-top text-slate-600">
                            @if ($customer->town || $customer->county)
                                <p>{{ $customer->town ?: '—' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $customer->county }}</p>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-top">
                            <p class="font-semibold text-brand-ink">{{ $customer->warranties_count }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $customer->active_warranties_count }} active</p>
                        </td>
                        <td class="px-4 py-3.5 align-top">
                            @if ($customer->marketing_consent)
                                <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Marketing</span>
                            @else
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">No marketing</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-top text-right">
                            <a href="{{ route('admin.customers.show', $customer) }}"
                               class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm font-medium text-slate-500 opacity-70 transition hover:bg-white hover:text-brand group-hover:opacity-100">
                                View
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center">
                            <p class="text-sm font-semibold text-brand-ink">No customers found</p>
                            <p class="mt-1 text-sm text-slate-500">Try a different search or clear your filters.</p>
                            @if ($hasSearch)
                                <a href="{{ route('admin.customers.index') }}" class="mt-4 inline-flex text-sm font-semibold text-brand hover:underline">Clear search</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Mobile --}}
<div class="space-y-3 lg:hidden">
    @forelse ($customers as $customer)
        <a href="{{ route('admin.customers.show', $customer) }}"
           class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-brand/30 hover:shadow">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate font-semibold text-brand-ink">{{ $customer->full_name }}</p>
                    <p class="mt-0.5 tabular-nums text-sm text-slate-500">{{ $customer->mobile_number ?? $customer->mobile_normalized }}</p>
                </div>
                <span class="shrink-0 text-xs font-semibold text-slate-500">{{ $customer->warranties_count }} wty</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-500">
                <div>
                    <p class="uppercase tracking-wide text-slate-400">Email</p>
                    <p class="mt-0.5 truncate font-medium text-slate-700">{{ $customer->email ?: '—' }}</p>
                </div>
                <div>
                    <p class="uppercase tracking-wide text-slate-400">Active</p>
                    <p class="mt-0.5 font-medium text-slate-700">{{ $customer->active_warranties_count }}</p>
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-12 text-center">
            <p class="text-sm font-semibold text-brand-ink">No customers found</p>
            <p class="mt-1 text-sm text-slate-500">Try a different search or clear your filters.</p>
        </div>
    @endforelse
</div>

@if ($customers->hasPages())
    <div class="mt-4">{{ $customers->links() }}</div>
@endif
@endsection
