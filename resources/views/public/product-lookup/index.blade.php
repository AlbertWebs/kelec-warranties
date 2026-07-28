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
                <p class="mt-1 text-xs text-slate-500">Local database is searched first, then Odoo if needed.</p>
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

    <div x-show="product" x-transition class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-brand-ink" x-text="product?.name"></h2>
            <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700" x-text="sourceLabel"></span>
        </div>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
            <div><dt class="text-slate-500">SKU</dt><dd class="font-semibold text-brand-ink" x-text="product?.default_code || '—'"></dd></div>
            <div><dt class="text-slate-500">Barcode</dt><dd class="font-semibold text-brand-ink" x-text="product?.barcode || '—'"></dd></div>
            <div><dt class="text-slate-500">Serial</dt><dd class="font-semibold text-brand-ink" x-text="product?.serial_number || '—'"></dd></div>
            <div><dt class="text-slate-500">Category</dt><dd class="font-semibold text-brand-ink" x-text="product?.category_name || '—'"></dd></div>
            <div><dt class="text-slate-500">Brand</dt><dd class="font-semibold text-brand-ink" x-text="product?.brand_name || 'K-Elec'"></dd></div>
            <div><dt class="text-slate-500">Tracking</dt><dd class="font-semibold text-brand-ink" x-text="product?.tracking || '—'"></dd></div>
        </dl>
    </div>
</section>

<script>
function productLookup() {
    return {
        query: '',
        loading: false,
        error: '',
        product: null,
        sourceLabel: '',
        async lookup() {
            const search = this.query.trim();
            if (!search || this.loading) return;

            this.loading = true;
            this.error = '';
            this.product = null;
            this.sourceLabel = '';

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
                this.sourceLabel = payload.source === 'odoo' ? 'From Odoo' : 'From Local';
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

