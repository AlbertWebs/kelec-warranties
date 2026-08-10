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

    <div x-show="error" x-transition class="mt-4 overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm">
        <div class="border-b border-red-100 bg-red-50 px-5 py-4 sm:px-6">
            <p class="text-sm text-red-700" x-text="error"></p>
            <button type="button" @click="lookup" class="mt-2 text-sm font-semibold text-red-800 underline underline-offset-2" x-show="query && !loading">Retry</button>
        </div>
        <div x-show="canRegister" class="bg-slate-50/70 px-5 py-4 sm:px-6">
            <p class="text-sm text-slate-600">This product is not registered for warranty yet.</p>
            <a :href="registerUrl" class="btn-brand mt-3 inline-flex w-full items-center justify-center px-4 py-2.5 text-sm sm:w-auto">
                Register warranty
            </a>
        </div>
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
        </div>

        <dl class="divide-y divide-slate-100">
            <div class="px-5 py-4 sm:px-6">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Product name</dt>
                <dd class="mt-1 text-sm font-semibold text-brand-ink" x-text="product?.name || '—'"></dd>
            </div>
            <div class="px-5 py-4 sm:px-6">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Model</dt>
                <dd class="mt-1 font-mono text-sm font-semibold tracking-wide text-brand-ink" x-text="product?.model || '—'"></dd>
            </div>
            <div class="px-5 py-4 sm:px-6">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Category</dt>
                <dd class="mt-1 text-sm font-semibold text-brand-ink" x-text="product?.category_name || '—'"></dd>
            </div>
            <div class="px-5 py-4 sm:px-6">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Date of purchase</dt>
                <dd class="mt-1 text-sm font-semibold text-brand-ink" x-text="formatPurchaseDate(product?.purchase_date)"></dd>
            </div>
        </dl>

        <div x-show="canRegister" class="border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:px-6">
            <p class="text-sm text-slate-600">This product is not registered for warranty yet.</p>
            <a :href="registerUrl" class="btn-brand mt-3 inline-flex w-full items-center justify-center px-4 py-2.5 text-sm sm:w-auto">
                Register warranty
            </a>
        </div>

        <div x-show="isRegistered" class="border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:px-6">
            <p class="text-sm text-slate-600">
                This product already has a warranty registration
                <span x-show="warrantyReference"> (<span class="font-semibold text-brand-ink" x-text="warrantyReference"></span>)</span>.
            </p>
            <a href="{{ route('warranty.lookup') }}" class="btn-brand mt-3 inline-flex w-full items-center justify-center px-4 py-2.5 text-sm sm:w-auto">
                Look up warranty
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
        isRegistered: false,
        canRegister: false,
        warrantyReference: '',
        get registerUrl() {
            const base = @json(route('register-warranty.create'));
            const serial = this.product?.serial_number || this.product?.barcode || this.query.trim();
            return serial ? `${base}?serial=${encodeURIComponent(serial)}` : base;
        },
        formatPurchaseDate(value) {
            if (!value) return '—';
            const date = new Date(`${value}T00:00:00`);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },
        resetResultState() {
            this.error = '';
            this.product = null;
            this.isRegistered = false;
            this.canRegister = false;
            this.warrantyReference = '';
        },
        async lookup() {
            const search = this.query.trim();
            if (!search || this.loading) return;

            this.loading = true;
            this.resetResultState();

            try {
                const response = await fetch('{{ route('api.products.lookup') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ query: search }),
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    this.error = payload.message || 'We could not find a product matching the information provided.';
                    this.canRegister = Boolean(payload.can_register);
                    return;
                }

                this.product = payload.product;
                this.isRegistered = Boolean(payload.is_registered);
                this.canRegister = Boolean(payload.can_register);
                this.warrantyReference = payload.warranty_reference || '';
            } catch (e) {
                this.error = 'We could not complete the product lookup at the moment. Please try again shortly.';
                this.canRegister = false;
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection
