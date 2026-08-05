@extends('layouts.admin')

@section('title', 'Products')

@section('content')
@php
    $hasSearch = filled(request('q'));
    $source = request('source');
    $hasSourceFilter = in_array($source, ['odoo', 'local'], true);
@endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-brand-ink">
            @if ($source === 'odoo')
                Imported Odoo products
            @elseif ($source === 'local')
                Local products
            @else
                Products
            @endif
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ number_format($products->total()) }} {{ Str::plural('product', $products->total()) }}
            @if ($hasSearch || $hasSourceFilter)<span class="text-slate-400">· filtered</span>@endif
            @if ($source === 'odoo')
                <a href="{{ route('admin.odoo.products.index') }}" class="ml-2 font-medium text-brand hover:underline">Back to Odoo sync</a>
            @endif
        </p>
    </div>
    @can('products.manage')
        <a href="{{ route('admin.products.create') }}" class="inline-flex items-center rounded-lg bg-brand px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-dark">Add product</a>
    @endcan
</div>

<form method="GET" class="mb-4">
    @if ($hasSourceFilter)
        <input type="hidden" name="source" value="{{ $source }}">
    @endif
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative min-w-0 flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                </svg>
                <input name="q" value="{{ request('q') }}" placeholder="Search products…" class="w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Apply</button>
                @if ($hasSearch || $hasSourceFilter)
                    <a href="{{ route('admin.products.index') }}" class="text-sm font-medium text-brand hover:underline">Clear</a>
                @endif
            </div>
        </div>
    </div>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Model</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Warranty</th>
                    <th class="px-4 py-3">Source</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <tr class="group transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $product->name }}</td>
                        <td class="px-4 py-3.5 align-top font-mono text-[13px] text-slate-600">{{ $product->sku }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $product->model ?: '—' }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $product->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $product->resolvedWarrantyMonths() }} months</td>
                        <td class="px-4 py-3.5 align-top">
                            @if ($product->is_odoo_managed)
                                <span class="inline-flex rounded-md bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-600/20">Odoo sync</span>
                            @else
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">Local</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-top text-right">
                            <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm font-medium text-slate-500 opacity-70 transition hover:bg-white hover:text-brand group-hover:opacity-100">
                                Edit
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-16 text-center text-sm text-slate-500">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($products->hasPages())
    <div class="mt-4">{{ $products->links() }}</div>
@endif
@endsection
