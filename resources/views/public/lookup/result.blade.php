@extends('layouts.public')

@section('title', 'Warranty Details')

@section('content')
@php
    $isActive = $warranty->status === \App\Enums\WarrantyStatus::Active;
    $isPending = in_array($warranty->status, [
        \App\Enums\WarrantyStatus::PendingVerification,
        \App\Enums\WarrantyStatus::Submitted,
        \App\Enums\WarrantyStatus::UnderReview,
    ], true);
    $remaining = $warranty->remainingDays();
@endphp

@include('public.partials.warranty-tabs', ['activeTab' => 'lookup'])

<div class="mx-auto max-w-3xl">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div @class([
            'border-b px-6 py-8 text-center sm:px-10',
            'border-green-100 bg-gradient-to-br from-green-50 via-white to-brand-soft' => $isActive,
            'border-amber-100 bg-gradient-to-br from-amber-50 via-white to-brand-soft' => $isPending,
            'border-gray-100 bg-gradient-to-br from-gray-50 via-white to-brand-soft' => ! $isActive && ! $isPending,
        ])>
            <div @class([
                'mx-auto flex h-14 w-14 items-center justify-center rounded-full text-white shadow-lg',
                'bg-green-600 shadow-green-600/25' => $isActive,
                'bg-amber-500 shadow-amber-500/25' => $isPending,
                'bg-brand-navy shadow-brand-navy/25' => ! $isActive && ! $isPending,
            ])>
                @if ($isActive)
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                @else
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                @endif
            </div>
            <p @class([
                'mt-4 text-xs font-semibold uppercase tracking-wider',
                'text-green-700' => $isActive,
                'text-amber-700' => $isPending,
                'text-brand-navy' => ! $isActive && ! $isPending,
            ])>Warranty found</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-ink sm:text-4xl">Your warranty details</h1>
            <p class="mx-auto mt-3 max-w-xl text-base text-gray-600">
                Showing verified details for {{ $warranty->customer->maskedName() }}.
                @if ($isActive)
                    This warranty is <span class="font-semibold text-green-700">active</span>.
                @elseif ($isPending)
                    This warranty is <span class="font-semibold text-amber-700">awaiting verification</span>.
                @endif
            </p>
        </div>

        <div class="px-6 py-6 sm:px-10">
            @include('public.partials.warranty-summary', [
                'warranty' => $warranty,
                'referenceHint' => 'Keep your serial number and registered mobile for future lookups.',
                'fields' => [
                    ['label' => 'Customer', 'value' => $warranty->customer->maskedName()],
                    ['label' => 'Mobile', 'value' => $warranty->customer->maskedMobile()],
                    ['label' => 'Product', 'value' => $warranty->displayProductName()],
                    ['label' => 'Model', 'value' => $warranty->displayModel() ?? '—'],
                    ['label' => 'Serial number', 'value' => $warranty->serial_number, 'mono' => true],
                    ['label' => 'Place of purchase', 'value' => $warranty->purchaseSource?->name ?? $warranty->branch_name ?? '—'],
                    [
                        'label' => 'Coverage period',
                        'value' => optional($warranty->warranty_start_date)->format('d M Y') ?? '—',
                        'sub' => $warranty->warranty_expiry_date
                            ? 'Expires '.$warranty->warranty_expiry_date->format('d M Y')
                            : 'Expiry pending verification',
                    ],
                    [
                        'label' => 'Remaining',
                        'value' => $remaining !== null ? $remaining.' days' : '—',
                        'sub' => $warranty->warranty_duration_months
                            ? $warranty->warranty_duration_months.' months total coverage'
                            : null,
                    ],
                ],
            ])

            @if ($isPending)
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                    <p class="font-semibold">Verification in progress</p>
                    <p class="mt-1 text-amber-900/90">You can still view your certificate. We'll notify you when the warranty is fully activated.</p>
                </div>
            @endif

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <a href="{{ route('warranty.certificate', $warranty->reference) }}"
                   class="btn-brand inline-flex flex-1 items-center justify-center px-5 py-3 text-center sm:flex-none">
                    View certificate
                </a>
                <a href="{{ route('warranty.certificate.download', $warranty->reference) }}"
                   class="inline-flex flex-1 items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 font-semibold text-brand-ink hover:border-brand hover:text-brand sm:flex-none">
                    Download PDF
                </a>
                <form method="POST" action="{{ route('warranty.lookup.resend') }}" class="flex-1 sm:flex-none">
                    @csrf
                    <input type="hidden" name="serial_number" value="{{ $warranty->serial_number }}">
                    <input type="hidden" name="mobile_number" value="{{ $warranty->customer->mobile_number }}">
                    <button class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 font-semibold text-brand-ink hover:border-brand hover:text-brand">
                        Resend details
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-brand-ink">Need help?</h2>
            <p class="mt-2 text-sm text-gray-600">Contact support with your serial number.</p>
            <a href="{{ support_phone_tel() }}" class="mt-3 inline-block text-sm font-semibold text-brand hover:underline">{{ support_phone() }}</a>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-brand-ink">Search again</h2>
            <p class="mt-2 text-sm text-gray-600">Look up another warranty with a different reference.</p>
            <a href="{{ route('warranty.lookup') }}" class="mt-3 inline-block text-sm font-semibold text-brand hover:underline">New lookup →</a>
        </div>
    </div>
</div>
@endsection
