@extends('layouts.public')

@section('title', 'Warranty Lookup')

@section('content')
<div class="mx-auto max-w-xl">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gradient-to-br from-brand-soft via-white to-white px-6 py-8 text-center sm:px-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-brand">Secure lookup</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-ink">Find your warranty</h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-gray-600">
                Enter your warranty reference or serial number, plus the mobile number used at registration.
            </p>
        </div>

        <form method="POST" action="{{ route('warranty.lookup.store') }}" class="space-y-4 px-6 py-6 sm:px-8">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-brand-ink">Warranty reference</label>
                <input name="reference" value="{{ $reference }}"
                       class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                       placeholder="KEL-WTY-2026-000001"
                       autocomplete="off">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-brand-ink">Serial number</label>
                <input name="serial_number" value="{{ $serial_number ?? request('serial') }}"
                       class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                       placeholder="Product serial"
                       autocomplete="off">
                <p class="mt-1 text-xs text-gray-500">Provide reference or serial (or both). QR links prefill these fields.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-brand-ink">Registered mobile number</label>
                <input name="mobile_number" value="{{ old('mobile_number') }}" required
                       class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                       placeholder="07XXXXXXXX"
                       inputmode="tel"
                       autocomplete="tel">
                <p class="mt-1 text-xs text-gray-500">Required for privacy verification.</p>
            </div>
            <button class="btn-brand w-full py-3">Search warranty</button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-gray-500">
        Don't have a warranty yet?
        <a href="{{ route('register-warranty.create') }}" class="font-semibold text-brand hover:underline">Register here</a>
    </p>
</div>
@endsection
