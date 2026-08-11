@extends('layouts.admin')

@section('title', 'Warranties')

@section('content')
@php
    use App\Enums\WarrantyStatus;

    $activeFilters = collect([
        'q' => $filters['q'] ?? null,
        'status' => $filters['status'] ?? null,
        'purchase_source_id' => $filters['purchase_source_id'] ?? null,
        'product_category_id' => $filters['product_category_id'] ?? null,
        'dealer_id' => $filters['dealer_id'] ?? null,
        'registered_from' => $filters['registered_from'] ?? null,
        'registered_to' => $filters['registered_to'] ?? null,
    ])->filter(fn ($value) => filled($value));

    $statusLabels = collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
    $sourceLabels = $purchaseSources->pluck('name', 'id');
    $categoryLabels = $categories->pluck('name', 'id');
    $dealerLabels = $dealers->pluck('name', 'id');

    $quickStatuses = [
        WarrantyStatus::Active,
        WarrantyStatus::PendingVerification,
        WarrantyStatus::UnderReview,
        WarrantyStatus::Rejected,
        WarrantyStatus::Expired,
    ];

    $moreOpen = filled($filters['product_category_id'] ?? null)
        || filled($filters['dealer_id'] ?? null)
        || filled($filters['registered_from'] ?? null)
        || filled($filters['registered_to'] ?? null);
@endphp

<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="admin-page-title">Warranties</h1>
        <p class="admin-page-subtitle">
            {{ number_format($warranties->total()) }}
            {{ Str::plural('registration', $warranties->total()) }}
            @if ($activeFilters->isNotEmpty())
                <span class="text-slate-400">· filtered</span>
            @endif
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.warranties.pending') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">
            Pending queue
            @if ($pendingCount > 0)
                <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-md bg-amber-500 px-1.5 py-0.5 text-xs font-bold text-white">{{ $pendingCount }}</span>
            @endif
        </a>
        @can('export', App\Models\Warranty::class)
            <a href="{{ route('admin.warranties.export', request()->query()) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </a>
        @endcan
    </div>
</div>

<form method="GET" class="mb-4 space-y-3" x-data="{ open: {{ $moreOpen ? 'true' : 'false' }} }">
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative min-w-0 flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                </svg>
                <input
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Search name, phone, serial, reference…"
                    class="w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-brand focus:ring-brand"
                >
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:w-[28rem] lg:grid-cols-2">
                <select name="status" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <select name="purchase_source_id" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    <option value="">All sources</option>
                    @foreach ($purchaseSources as $source)
                        <option value="{{ $source->id }}" @selected(($filters['purchase_source_id'] ?? '') == $source->id)>{{ $source->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">
                    Apply
                </button>
                <button type="button" @click="open = !open" class="inline-flex items-center gap-1 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-brand-ink">
                    More
                    <svg class="h-4 w-4 transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                @if ($activeFilters->isNotEmpty())
                    <a href="{{ route('admin.warranties.index') }}" class="text-sm font-medium text-brand hover:underline">Clear</a>
                @endif
            </div>
        </div>

        <div x-show="open" x-cloak class="mt-3 grid gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Category</label>
                <select name="product_category_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['product_category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Dealer</label>
                <select name="dealer_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    <option value="">All dealers</option>
                    @foreach ($dealers as $dealer)
                        <option value="{{ $dealer->id }}" @selected(($filters['dealer_id'] ?? '') == $dealer->id)>{{ $dealer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Registered from</label>
                <input type="date" name="registered_from" value="{{ $filters['registered_from'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Registered to</label>
                <input type="date" name="registered_to" value="{{ $filters['registered_to'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-1.5">
        <a href="{{ route('admin.warranties.index', array_filter(['q' => $filters['q'] ?? null])) }}"
           @class([
               'rounded-md px-2.5 py-1 text-xs font-semibold transition',
               'bg-brand-navy text-white' => empty($filters['status']),
               'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => ! empty($filters['status']),
           ])>All</a>
        @foreach ($quickStatuses as $status)
            <a href="{{ route('admin.warranties.index', array_filter(['q' => $filters['q'] ?? null, 'status' => $status->value, 'purchase_source_id' => $filters['purchase_source_id'] ?? null])) }}"
               @class([
                   'rounded-md px-2.5 py-1 text-xs font-semibold transition ring-1 ring-inset',
                   $status->badgeClasses() => ($filters['status'] ?? '') === $status->value,
                   'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' => ($filters['status'] ?? '') !== $status->value,
               ])>{{ $status->label() }}</a>
        @endforeach
    </div>
</form>

@if ($activeFilters->isNotEmpty())
    <div class="mb-3 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Active</span>
        @foreach ($activeFilters as $key => $value)
            @php
                $label = match ($key) {
                    'q' => '“'.$value.'”',
                    'status' => $statusLabels[$value] ?? $value,
                    'purchase_source_id' => $sourceLabels[$value] ?? $value,
                    'product_category_id' => $categoryLabels[$value] ?? $value,
                    'dealer_id' => $dealerLabels[$value] ?? $value,
                    'registered_from' => 'From '.$value,
                    'registered_to' => 'To '.$value,
                    default => $value,
                };
            @endphp
            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $label }}</span>
        @endforeach
    </div>
@endif

{{-- Desktop table --}}
<div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:block">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Source</th>
                    <th class="px-4 py-3">Expiry</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($warranties as $warranty)
                    @php
                        $expiry = $warranty->warranty_expiry_date;
                        $daysLeft = $expiry ? (int) now()->startOfDay()->diffInDays($expiry->copy()->startOfDay(), false) : null;
                        $expiryTone = match (true) {
                            $warranty->status === WarrantyStatus::Expired || ($expiry && $expiry->isPast()) => 'text-red-600',
                            $expiry && $expiry->lte(now()->addDays(30)) => 'text-amber-700',
                            default => 'text-slate-700',
                        };
                    @endphp
                    <tr class="group transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top">
                            <a href="{{ route('admin.warranties.show', $warranty) }}" class="font-mono text-[13px] font-semibold text-brand hover:underline">
                                {{ $warranty->reference }}
                            </a>
                            <p class="mt-0.5 text-xs text-slate-400">{{ optional($warranty->registration_date ?? $warranty->created_at)->format('d M Y') }}</p>
                        </td>
                        <td class="px-4 py-3.5 align-top">
                            <p class="font-medium text-brand-ink">{{ $warranty->customer?->full_name ?? '—' }}</p>
                            <p class="mt-0.5 tabular-nums text-xs text-slate-500">{{ $warranty->customer?->mobile_number ?? $warranty->customer?->mobile_normalized }}</p>
                        </td>
                        <td class="px-4 py-3.5 align-top">
                            <p class="max-w-[14rem] truncate font-medium text-brand-ink" title="{{ $warranty->displayProductName() }}">{{ $warranty->displayProductName() }}</p>
                            <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $warranty->serial_number }}</p>
                        </td>
                        <td class="px-4 py-3.5 align-top">{{ $warranty->purchaseSource?->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 align-top">
                            <p class="font-medium {{ $expiryTone }}">{{ optional($expiry)->format('d M Y') ?? '—' }}</p>
                            @if ($expiry && $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 30)
                                <p class="mt-0.5 text-xs text-amber-600">{{ $daysLeft }} days left</p>
                            @elseif ($expiry && $expiry->isPast())
                                <p class="mt-0.5 text-xs text-red-500">Expired</p>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-top"><x-admin.status-badge :status="$warranty->status" /></td>
                        <td class="px-4 py-3.5 align-top text-right">
                            <div class="inline-flex items-center justify-end gap-1 opacity-70 transition group-hover:opacity-100">
                                @can('resendNotification', $warranty)
                                    <form method="POST" action="{{ route('admin.warranties.resend', $warranty) }}" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm font-medium text-slate-500 transition hover:bg-white hover:text-brand"
                                            title="Send email and SMS notification"
                                            onclick="return confirm('Send notification to {{ addslashes($warranty->customer?->full_name ?? 'customer') }}?');"
                                        >
                                            Notify
                                        </button>
                                    </form>
                                @endcan
                                <a href="{{ route('admin.warranties.show', $warranty) }}" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm font-medium text-slate-500 transition hover:bg-white hover:text-brand">
                                    View
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-16 text-center">
                            <div class="mx-auto max-w-sm">
                                <p class="text-sm font-semibold text-brand-ink">No warranties found</p>
                                <p class="mt-1 text-sm text-slate-500">Try a different search or clear your filters.</p>
                                @if ($activeFilters->isNotEmpty())
                                    <a href="{{ route('admin.warranties.index') }}" class="mt-4 inline-flex text-sm font-semibold text-brand hover:underline">Clear filters</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Mobile cards --}}
<div class="space-y-3 lg:hidden">
    @forelse ($warranties as $warranty)
        @php
            $expiry = $warranty->warranty_expiry_date;
        @endphp
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-brand/30 hover:shadow">
            <a href="{{ route('admin.warranties.show', $warranty) }}" class="block">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-semibold text-brand">{{ $warranty->reference }}</p>
                        <p class="mt-1 truncate font-medium text-brand-ink">{{ $warranty->customer?->full_name ?? '—' }}</p>
                    </div>
                    <x-admin.status-badge :status="$warranty->status" />
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-500">
                    <div>
                        <p class="uppercase tracking-wide text-slate-400">Product</p>
                        <p class="mt-0.5 truncate font-medium text-slate-700">{{ $warranty->displayProductName() }}</p>
                    </div>
                    <div>
                        <p class="uppercase tracking-wide text-slate-400">Serial</p>
                        <p class="mt-0.5 font-mono font-medium text-slate-700">{{ $warranty->serial_number }}</p>
                    </div>
                    <div>
                        <p class="uppercase tracking-wide text-slate-400">Source</p>
                        <p class="mt-0.5 font-medium text-slate-700">{{ $warranty->purchaseSource?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="uppercase tracking-wide text-slate-400">Expiry</p>
                        <p class="mt-0.5 font-medium text-slate-700">{{ optional($expiry)->format('d M Y') ?? '—' }}</p>
                    </div>
                </div>
            </a>
            <div class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
                @can('resendNotification', $warranty)
                    <form method="POST" action="{{ route('admin.warranties.resend', $warranty) }}" class="flex-1">
                        @csrf
                        <button
                            type="submit"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-brand-ink transition hover:bg-slate-50"
                            onclick="return confirm('Send notification to {{ addslashes($warranty->customer?->full_name ?? 'customer') }}?');"
                        >
                            Notify
                        </button>
                    </form>
                @endcan
                <a href="{{ route('admin.warranties.show', $warranty) }}"
                   class="flex-1 rounded-lg bg-brand-navy px-3 py-2 text-center text-sm font-semibold text-white transition hover:bg-brand-ink">
                    View
                </a>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-12 text-center">
            <p class="text-sm font-semibold text-brand-ink">No warranties found</p>
            <p class="mt-1 text-sm text-slate-500">Try a different search or clear your filters.</p>
        </div>
    @endforelse
</div>

@if ($warranties->hasPages())
    <div class="mt-4">{{ $warranties->links() }}</div>
@endif
@endsection
