@extends('layouts.public')

@section('title', 'Registration Successful')

@section('content')
@php
    $isActive = $warranty->status === \App\Enums\WarrantyStatus::Active;
    $isPending = in_array($warranty->status, [
        \App\Enums\WarrantyStatus::PendingVerification,
        \App\Enums\WarrantyStatus::Submitted,
        \App\Enums\WarrantyStatus::UnderReview,
    ], true);
@endphp

<div class="mx-auto max-w-3xl" x-data="{ copied: false }">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-green-100 bg-gradient-to-br from-green-50 via-white to-brand-soft px-6 py-8 text-center sm:px-10">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-600 text-white shadow-lg shadow-green-600/25">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-green-700">Registration complete</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-ink sm:text-4xl">You're all set</h1>
            <p class="mx-auto mt-3 max-w-xl text-base text-gray-600">
                Thanks{{ $warranty->customer?->full_name ? ', '.$warranty->customer->full_name : '' }}.
                Your K-Elec warranty registration has been saved
                @if ($isActive)
                    and is <span class="font-semibold text-green-700">active</span>.
                @elseif ($isPending)
                    and is <span class="font-semibold text-amber-700">awaiting verification</span>.
                @else
                    with status <span class="font-semibold">{{ $warranty->status->label() }}</span>.
                @endif
            </p>
        </div>

        <div class="px-6 py-6 sm:px-10">
            <div class="rounded-xl border border-brand/20 bg-brand-soft/80 p-4 sm:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-brand-navy">Warranty reference</p>
                        <p class="mt-1 break-all font-mono text-xl font-bold tracking-wide text-brand-ink sm:text-2xl" id="warranty-reference">{{ $warranty->reference }}</p>
                        <p class="mt-1 text-sm text-gray-600">Save this number — you'll need it (plus your mobile) to look up your warranty.</p>
                    </div>
                    <button type="button"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark"
                            @click="navigator.clipboard.writeText(@js($warranty->reference)); copied = true; setTimeout(() => copied = false, 2000)">
                        <span x-text="copied ? 'Copied' : 'Copy reference'"></span>
                    </button>
                </div>
            </div>

            <dl class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</dt>
                    <dd class="mt-1">
                        <span @class([
                            'inline-flex rounded-md px-2.5 py-1 text-sm font-semibold',
                            'bg-green-100 text-green-800' => $isActive,
                            'bg-amber-100 text-amber-800' => $isPending,
                            'bg-gray-100 text-gray-800' => ! $isActive && ! $isPending,
                        ])>{{ $warranty->status->label() }}</span>
                    </dd>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Product</dt>
                    <dd class="mt-1 font-semibold text-brand-ink">{{ $warranty->displayProductName() }}</dd>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Serial number</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold text-brand-ink">{{ $warranty->serial_number }}</dd>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-4">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Coverage</dt>
                    <dd class="mt-1 font-semibold text-brand-ink">
                        {{ $warranty->warranty_duration_months }} months
                        @if ($warranty->warranty_expiry_date)
                            <span class="block text-sm font-normal text-gray-600">Expires {{ $warranty->warranty_expiry_date->format('d M Y') }}</span>
                        @else
                            <span class="block text-sm font-normal text-gray-600">Expiry after verification</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @if ($isPending)
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                    <p class="font-semibold">What happens next?</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-amber-900/90">
                        <li>Our team may review your purchase details if needed.</li>
                        <li>You'll get an SMS/email update when the warranty is activated.</li>
                        <li>You can check status anytime via Lookup using your reference and mobile number.</li>
                    </ul>
                </div>
            @elseif ($isActive)
                <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-4 text-sm text-green-900">
                    <p class="font-semibold">Your warranty is active</p>
                    <p class="mt-1 text-green-900/90">View or download your certificate below. Keep your reference safe for future claims and support.</p>
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
                <a href="{{ route('warranty.lookup') }}"
                   class="inline-flex flex-1 items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 font-semibold text-brand-ink hover:border-brand hover:text-brand sm:flex-none">
                    Lookup warranty
                </a>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-brand-ink">Need help?</h2>
            <p class="mt-2 text-sm text-gray-600">Contact K-Elec support with your warranty reference.</p>
            <a href="tel:+254716052243" class="mt-3 inline-block text-sm font-semibold text-brand hover:underline">0716 052 243</a>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-brand-ink">Register another?</h2>
            <p class="mt-2 text-sm text-gray-600">Have another appliance? You can submit a new registration now.</p>
            <a href="{{ route('register-warranty.create') }}" class="mt-3 inline-block text-sm font-semibold text-brand hover:underline">Start new registration →</a>
        </div>
    </div>
</div>
@endsection
