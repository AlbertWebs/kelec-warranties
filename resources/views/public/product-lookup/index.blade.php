@extends('layouts.public')

@section('title', 'Product Lookup')

@section('content')
<section class="mx-auto max-w-2xl" x-data="productLookup()">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-brand-ink">Product Lookup</h1>
        <p class="mt-2 text-sm text-slate-600">Search by serial number, barcode, SKU/internal reference, or product name.</p>

        <form class="mt-5 space-y-4" @submit.prevent="lookup">
            <div>
                <label class="mb-1 block text-sm font-medium text-brand-ink">Search product</label>
                <input x-model="query" :disabled="loading" class="w-full rounded-lg border-slate-300 focus:border-brand focus:ring-brand" placeholder="e.g. SERIAL-123456, 123456789, SKU-001">
            </div>
            <button type="submit" :disabled="loading || !query.trim()" class="btn-brand inline-flex w-full items-center justify-center gap-2 py-2.5 disabled:opacity-60">
                <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" class="opacity-20" stroke="currentColor" stroke-width="3"></circle>
                    <path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" class="opacity-90"></path>
                </svg>
                <span x-text="loading ? 'Searching for your product...' : 'Search Product'"></span>
            </button>
        </form>
    </div>

    <div x-show="error" x-transition class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <p x-text="error"></p>
        <button type="button" @click="lookup" class="mt-2 text-sm font-semibold underline underline-offset-2" x-show="query && !loading">Retry</button>
    </div>

    <div x-show="product" x-cloak x-transition class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-green-100 bg-gradient-to-br from-green-50 via-white to-brand-soft px-5 py-6 sm:px-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-green-600 text-white shadow-lg shadow-green-600/25">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                </svg>
            </div>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-green-700">Product found</p>
            <h2 class="mt-1 text-xl font-bold tracking-tight text-brand-ink sm:text-2xl" x-text="product?.name"></h2>
            <p class="mt-1 text-sm text-slate-600" x-show="product?.brand_name || product?.category_name">
                <span x-text="product?.brand_name || 'K-Elec'"></span>
                <span x-show="product?.category_name"> · <span x-text="product?.category_name"></span></span>
            </p>
        </div>

        <dl class="grid gap-0 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-y-0">
            <template x-for="field in visibleFields" :key="field.label">
                <div class="px-5 py-4 sm:border-b sm:border-slate-100 sm:px-6">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500" x-text="field.label"></dt>
                    <dd class="mt-1 text-sm font-semibold text-brand-ink" :class="field.mono ? 'font-mono tracking-wide' : ''" x-text="field.value"></dd>
                </div>
            </template>
        </dl>

        <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:px-6">
            <p class="text-sm text-slate-600">Ready to protect this appliance?</p>
            <a :href="registerUrl" class="btn-brand mt-3 inline-flex w-full items-center justify-center px-4 py-2.5 text-sm sm:w-auto">
                Register warranty
            </a>
        </div>
    </div>
</section>

<script>
function productLookup() {
    return {
        query: '',
        loading: false,
        error: '',
        product: null,
        get visibleFields() {
            if (!this.product) return [];

            const fields = [
                { label: 'SKU / reference', value: this.product.default_code, mono: true },
                { label: 'Barcode', value: this.product.barcode, mono: true },
                { label: 'Serial number', value: this.product.serial_number, mono: true },
                { label: 'Brand', value: this.product.brand_name || 'K-Elec' },
                { label: 'Category', value: this.product.category_name },
                {
                    label: 'Tracking',
                    value: this.product.tracking
                        ? this.product.tracking.charAt(0).toUpperCase() + this.product.tracking.slice(1)
                        : null,
                },
            ];

            return fields.filter((field) => field.value);
        },
        get registerUrl() {
            const base = @json(route('register-warranty.create'));
            const serial = this.product?.serial_number || this.product?.barcode || '';
            return serial ? `${base}?serial=${encodeURIComponent(serial)}` : base;
        },
        async lookup() {
            const search = this.query.trim();
            if (!search || this.loading) return;

            this.loading = true;
            this.error = '';
            this.product = null;

            try {
                const response = await fetch('{{ route('api.products.lookup') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ query: search }),
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    this.error = payload.message || 'We could not find a product matching the information provided.';
                    return;
                }

                this.product = payload.product;
            } catch (e) {
                this.error = 'We could not complete the product lookup at the moment. Please try again shortly.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection
