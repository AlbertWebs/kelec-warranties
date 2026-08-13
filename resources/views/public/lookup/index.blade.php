@extends('layouts.public')

@section('title', 'Warranty Lookup')

@section('content')
<div class="mx-auto max-w-xl" x-data="warrantyLookupAjax()">
    @include('public.partials.warranty-tabs', ['activeTab' => 'lookup'])

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gradient-to-br from-brand-soft via-white to-white px-6 py-8 text-center sm:px-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-brand">Secure lookup</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-ink">Find your warranty</h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-gray-600">
                Enter the product serial number and the mobile number used at registration.
            </p>
        </div>

        <form method="POST" action="{{ route('warranty.lookup.store') }}" class="space-y-4 px-6 py-6 sm:px-8" @submit.prevent="search">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-brand-ink">Serial number</label>
                <input name="serial_number" x-model="form.serial_number" value="{{ $serial_number ?? '' }}" required :disabled="loading"
                       class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                       placeholder="Product serial"
                       autocomplete="off">
                @error('serial_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-brand-ink">Registered mobile number</label>
                <input name="mobile_number" x-model="form.mobile_number" value="{{ old('mobile_number') }}" required :disabled="loading"
                       class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                       placeholder="07XXXXXXXX"
                       inputmode="tel"
                       autocomplete="tel">
                <p class="mt-1 text-xs text-gray-500">Required for privacy verification.</p>
                @error('mobile_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button class="btn-brand flex w-full items-center justify-center gap-2 py-3 disabled:cursor-not-allowed disabled:opacity-70" :disabled="loading">
                <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" class="opacity-20" stroke="currentColor" stroke-width="3"></circle>
                    <path d="M22 12a10 10 0 00-10-10" class="opacity-90" stroke="currentColor" stroke-width="3"></path>
                </svg>
                <span x-text="loading ? 'Searching…' : 'Search warranty'"></span>
            </button>
        </form>
    </div>

    <div x-show="error" x-transition class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <p x-text="error"></p>
    </div>

    <div x-show="result" x-transition class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-brand-ink">Warranty details</h2>
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Reference</dt><dd class="font-semibold text-brand-ink" x-text="result?.reference"></dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-semibold text-brand-ink" x-text="result?.status"></dd></div>
            <div><dt class="text-slate-500">Customer</dt><dd x-text="result?.customer_name"></dd></div>
            <div><dt class="text-slate-500">Mobile</dt><dd x-text="result?.mobile"></dd></div>
            <div><dt class="text-slate-500">Product</dt><dd x-text="result?.product"></dd></div>
            <div><dt class="text-slate-500">Model</dt><dd x-text="result?.model"></dd></div>
            <div><dt class="text-slate-500">Serial</dt><dd x-text="result?.serial_number"></dd></div>
            <div><dt class="text-slate-500">Place of purchase</dt><dd x-text="result?.purchase_source"></dd></div>
            <div><dt class="text-slate-500">Start date</dt><dd x-text="result?.warranty_start_date"></dd></div>
            <div><dt class="text-slate-500">Expiry date</dt><dd x-text="result?.warranty_expiry_date"></dd></div>
        </dl>

        <div class="mt-5 flex flex-wrap gap-2">
            <a :href="result?.certificate_url" class="btn-brand px-4 py-2 text-sm">View certificate</a>
            <a :href="result?.certificate_pdf_url" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-brand-ink hover:border-brand hover:text-brand">Download PDF</a>
        </div>
    </div>

    <p class="mt-6 text-center text-sm text-gray-500">
        Don't have a warranty yet?
        <a href="{{ route('register-warranty.create') }}" class="font-semibold text-brand hover:underline">Register here</a>
    </p>
</div>

<script>
function warrantyLookupAjax() {
    return {
        loading: false,
        error: '',
        result: null,
        form: {
            serial_number: @js($serial_number ?? ''),
            mobile_number: @js(old('mobile_number', '')),
        },
        async search() {
            if (this.loading) return;

            this.loading = true;
            this.error = '';
            this.result = null;

            try {
                const response = await fetch(@js(route('api.warranties.lookup')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });

                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    this.error = payload.message || 'No warranty matched the serial number and mobile provided. Please check and try again.';
                    return;
                }

                this.result = payload.warranty;
            } catch (e) {
                this.error = 'We could not complete the warranty lookup at the moment. Please try again shortly.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
