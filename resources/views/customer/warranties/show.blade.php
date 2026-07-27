@extends('layouts.public')

@section('title', $warranty->reference)

@section('content')
@php
    use App\Enums\WarrantyStatus;

    $canClaim = $warranty->isActive();
    $remainingDays = $warranty->remainingDays();
    $expiry = $warranty->warranty_expiry_date;
@endphp

<a href="{{ route('customer.warranties.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 transition hover:text-brand">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
    Back to my warranties
</a>

<div class="mt-3 mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between" x-data="{ copied: false }">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="font-mono text-2xl font-bold tracking-tight text-brand-ink sm:text-3xl">{{ $warranty->reference }}</h1>
            <x-admin.status-badge :status="$warranty->status" class="text-sm" />
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-gray-500 transition hover:bg-white hover:text-brand-ink"
                @click="navigator.clipboard.writeText(@js($warranty->reference)); copied = true; setTimeout(() => copied = false, 1800)"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span x-text="copied ? 'Copied' : 'Copy'"></span>
            </button>
        </div>
        <p class="mt-1.5 text-sm text-gray-600">
            {{ $warranty->displayProductName() }}
            @if ($warranty->displayModel())
                <span class="text-gray-400">·</span> {{ $warranty->displayModel() }}
            @endif
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if ($canClaim)
            <a href="{{ route('customer.claims.create', ['warranty_id' => $warranty->id]) }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-dark">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                File a claim
            </a>
        @endif
        <a href="{{ route('warranty.certificate', $warranty->reference) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-brand-ink transition hover:border-brand hover:text-brand">
            Certificate
            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
    </div>
</div>

@if (! $canClaim)
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold">Claims unavailable</p>
        <p class="mt-0.5">
            You can file a claim only while this warranty is <span class="font-semibold">Active</span>.
            Current status: {{ $warranty->status->label() }}.
        </p>
    </div>
@endif

<div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Status</p>
        <div class="mt-2"><x-admin.status-badge :status="$warranty->status" class="text-sm" /></div>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Warranty start</p>
        <p class="mt-1 text-lg font-semibold text-brand-ink">{{ optional($warranty->warranty_start_date)->format('d M Y') ?? '—' }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Expiry</p>
        <p @class([
            'mt-1 text-lg font-semibold',
            'text-red-600' => $expiry && $expiry->isPast(),
            'text-amber-700' => $expiry && ! $expiry->isPast() && $remainingDays !== null && $remainingDays <= 30,
            'text-brand-ink' => ! ($expiry && ($expiry->isPast() || ($remainingDays !== null && $remainingDays <= 30))),
        ])>{{ optional($expiry)->format('d M Y') ?? '—' }}</p>
        @if ($remainingDays !== null && $remainingDays >= 0)
            <p class="mt-0.5 text-xs text-gray-500">{{ $remainingDays }} days remaining</p>
        @elseif ($expiry && $expiry->isPast())
            <p class="mt-0.5 text-xs text-red-500">Expired</p>
        @endif
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Purchase source</p>
        <p class="mt-1 text-lg font-semibold text-brand-ink">{{ $warranty->purchaseSource?->name ?? '—' }}</p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-brand-ink">Product &amp; purchase</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-gray-50/80 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Serial number</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold text-brand-ink">{{ $warranty->serial_number }}</dd>
                </div>
                <div class="rounded-lg bg-gray-50/80 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Model</dt>
                    <dd class="mt-1 font-semibold text-brand-ink">{{ $warranty->displayModel() ?: '—' }}</dd>
                </div>
                <div class="rounded-lg bg-gray-50/80 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Purchase date</dt>
                    <dd class="mt-1 font-semibold text-brand-ink">{{ optional($warranty->purchase_date)->format('d M Y') ?? '—' }}</dd>
                </div>
                <div class="rounded-lg bg-gray-50/80 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Branch / dealer</dt>
                    <dd class="mt-1 font-semibold text-brand-ink">{{ $warranty->dealer?->name ?? $warranty->branch_name ?? '—' }}</dd>
                </div>
            </dl>
            @if ($warranty->customer_notes)
                <div class="mt-4 rounded-lg border border-gray-100 p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Your notes</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ $warranty->customer_notes }}</p>
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-brand-ink">Claims</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Service requests filed against this warranty.</p>
                </div>
                @if ($canClaim)
                    <a href="{{ route('customer.claims.create', ['warranty_id' => $warranty->id]) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-brand/30 bg-brand-light px-3.5 py-2 text-sm font-semibold text-brand-dark transition hover:bg-brand hover:text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New claim
                    </a>
                @endif
            </div>

            @if ($warranty->claims->isEmpty())
                <div class="mt-5 rounded-xl border border-dashed border-gray-200 px-4 py-10 text-center">
                    <p class="text-sm font-semibold text-brand-ink">No claims yet</p>
                    <p class="mt-1 text-sm text-gray-500">
                        @if ($canClaim)
                            Need service or a replacement? File a claim against this active warranty.
                        @else
                            Claims open once the warranty is active.
                        @endif
                    </p>
                    @if ($canClaim)
                        <a href="{{ route('customer.claims.create', ['warranty_id' => $warranty->id]) }}" class="btn-brand mt-5 inline-flex items-center gap-2 !px-4 !py-2.5 text-sm">
                            File a claim
                        </a>
                    @endif
                </div>
            @else
                <ul class="mt-4 divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200">
                    @foreach ($warranty->claims as $claim)
                        <li>
                            <a href="{{ route('customer.claims.show', $claim) }}" class="flex items-center justify-between gap-3 px-4 py-3.5 transition hover:bg-brand-soft/70">
                                <div class="min-w-0">
                                    <p class="font-mono text-sm font-semibold text-brand">{{ $claim->reference }}</p>
                                    <p class="mt-0.5 truncate text-sm text-gray-600">{{ $claim->subject }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <x-admin.status-badge :status="$claim->status" />
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <aside class="space-y-6">
        @if ($canClaim)
            <section class="rounded-xl border border-brand/20 bg-gradient-to-br from-brand to-brand-dark p-5 text-white shadow-sm">
                <h2 class="text-base font-semibold">Need help with this product?</h2>
                <p class="mt-1.5 text-sm text-white/80">Submit a warranty claim and our team will follow up.</p>
                <a href="{{ route('customer.claims.create', ['warranty_id' => $warranty->id]) }}"
                   class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-brand-dark transition hover:bg-brand-soft">
                    File a claim
                </a>
            </section>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-brand-ink">Quick links</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li>
                    <a href="{{ route('warranty.certificate', $warranty->reference) }}" class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2.5 font-medium text-brand-ink transition hover:border-brand/20 hover:bg-brand-soft/60">
                        View certificate
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('customer.claims.index') }}" class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2.5 font-medium text-brand-ink transition hover:border-brand/20 hover:bg-brand-soft/60">
                        All my claims
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('warranty-terms') }}" class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2.5 font-medium text-brand-ink transition hover:border-brand/20 hover:bg-brand-soft/60">
                        Warranty terms
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </li>
            </ul>
        </section>

        @if ($warranty->status === WarrantyStatus::Rejected && $warranty->rejection_reason)
            <section class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
                <p class="font-semibold">Rejection reason</p>
                <p class="mt-1">{{ $warranty->rejection_reason }}</p>
            </section>
        @endif
    </aside>
</div>
@endsection
