@extends('layouts.public')

@section('title', 'Warranty Certificate')

@section('content')
@php
    $isActive = $warranty->status === \App\Enums\WarrantyStatus::Active;
    $isPending = in_array($warranty->status, [
        \App\Enums\WarrantyStatus::PendingVerification,
        \App\Enums\WarrantyStatus::Submitted,
        \App\Enums\WarrantyStatus::UnderReview,
    ], true);
    $lookupUrl = route('warranty.lookup', ['reference' => $warranty->reference]);
@endphp

<div class="mx-auto max-w-3xl">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-brand/10 bg-gradient-to-br from-brand-light/60 via-white to-brand-soft px-6 py-8 text-center sm:px-10">
            <div class="mx-auto mb-4 inline-flex items-center overflow-hidden">
                <x-application-logo class="h-10 w-auto" />
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-brand">Official document</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-ink sm:text-4xl">Warranty Certificate</h1>
            <p class="mx-auto mt-3 max-w-xl text-base text-gray-600">
                Issued for {{ $warranty->customer->full_name }} ·
                @if ($isActive)
                    <span class="font-semibold text-green-700">Active coverage</span>
                @elseif ($isPending)
                    <span class="font-semibold text-amber-700">Pending verification</span>
                @else
                    {{ $warranty->status->label() }}
                @endif
            </p>
        </div>

        <div class="px-6 py-6 sm:px-10">
            @include('public.partials.warranty-summary', [
                'warranty' => $warranty,
                'referenceHint' => 'Quote this reference for claims and support.',
                'fields' => [
                    ['label' => 'Customer', 'value' => $warranty->customer->full_name],
                    ['label' => 'Product', 'value' => $warranty->displayProductName()],
                    ['label' => 'Model', 'value' => $warranty->displayModel() ?? '—'],
                    ['label' => 'Serial number', 'value' => $warranty->serial_number, 'mono' => true],
                    ['label' => 'Purchase source', 'value' => $warranty->purchaseSource?->name ?? $warranty->branch_name ?? '—'],
                    ['label' => 'Purchase date', 'value' => optional($warranty->purchase_date)->format('d M Y') ?? '—'],
                    ['label' => 'Warranty start', 'value' => optional($warranty->warranty_start_date)->format('d M Y') ?? '—'],
                    [
                        'label' => 'Warranty expiry',
                        'value' => optional($warranty->warranty_expiry_date)->format('d M Y') ?? 'Pending verification',
                        'sub' => $warranty->warranty_duration_months
                            ? $warranty->warranty_duration_months.' months coverage'
                            : null,
                    ],
                ],
            ])

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <a href="{{ route('warranty.certificate.download', $warranty->reference) }}"
                   class="btn-brand inline-flex flex-1 items-center justify-center px-5 py-3 text-center sm:flex-none">
                    Download PDF
                </a>
                <a href="{{ route('warranty.lookup', ['reference' => $warranty->reference]) }}"
                   class="inline-flex flex-1 items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 font-semibold text-brand-ink hover:border-brand hover:text-brand sm:flex-none">
                    Secure lookup
                </a>
                <a href="{{ route('warranty-terms') }}"
                   class="inline-flex flex-1 items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 font-semibold text-brand-ink hover:border-brand hover:text-brand sm:flex-none">
                    Warranty Terms
                </a>
            </div>

            <div class="mt-8 grid gap-6 rounded-xl border border-gray-100 bg-gray-50/80 p-5 sm:grid-cols-[1fr_auto] sm:items-center">
                <div>
                    <h2 class="text-sm font-semibold text-brand-ink">Secure lookup QR</h2>
                    <p class="mt-1 text-sm text-gray-600">Scan to open lookup. Mobile verification is still required.</p>
                    <p class="mt-3 break-all text-xs text-brand">
                        <a href="{{ $lookupUrl }}" class="hover:underline">{{ $lookupUrl }}</a>
                    </p>
                </div>
                <img class="mx-auto h-32 w-32 rounded-lg border border-white bg-white p-2 shadow-sm"
                     alt="Warranty lookup QR"
                     src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($lookupUrl) }}">
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-brand-ink">Need help?</h2>
            <p class="mt-2 text-sm text-gray-600">Support: K-Elec · quote your reference when you call.</p>
            <a href="{{ support_phone_tel() }}" class="mt-3 inline-block text-sm font-semibold text-brand hover:underline">{{ support_phone() }}</a>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-brand-ink">Register another product</h2>
            <p class="mt-2 text-sm text-gray-600">Submit a new warranty for another appliance.</p>
            <a href="{{ route('register-warranty.create') }}" class="mt-3 inline-block text-sm font-semibold text-brand hover:underline">Start registration →</a>
        </div>
    </div>
</div>
@endsection
