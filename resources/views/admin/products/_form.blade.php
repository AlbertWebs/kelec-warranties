<div class="space-y-6">
    <section class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Core Product Details</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-brand-ink">Product name <span class="text-red-600">*</span></label>
                <input id="name" name="name" value="{{ old('name', $product?->name) }}" required placeholder="e.g. K-Elec Cooker 90x60"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>

            <div>
                <label for="product_category_id" class="block text-sm font-medium text-brand-ink">Category</label>
                <select id="product_category_id" name="product_category_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                    <option value="">No category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('product_category_id', $product?->product_category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="brand" class="block text-sm font-medium text-brand-ink">Brand</label>
                <input id="brand" name="brand" value="{{ old('brand', $product?->brand ?? 'K-Elec') }}" placeholder="K-Elec"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>

            <div>
                <label for="model" class="block text-sm font-medium text-brand-ink">Model</label>
                <input id="model" name="model" value="{{ old('model', $product?->model) }}" placeholder="e.g. KE-CKR-900"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>

            <div>
                <label for="product_code" class="block text-sm font-medium text-brand-ink">Product code</label>
                <input id="product_code" name="product_code" value="{{ old('product_code', $product?->product_code) }}" placeholder="Internal code"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Identifiers & Lookup</h2>
        <p class="mt-1 text-xs text-slate-500">These values improve customer product lookup and Odoo sync matching.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div>
                <label for="sku" class="block text-sm font-medium text-brand-ink">SKU</label>
                <input id="sku" name="sku" value="{{ old('sku', $product?->sku) }}" placeholder="e.g. SKU-001"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="default_code" class="block text-sm font-medium text-brand-ink">Internal reference</label>
                <input id="default_code" name="default_code" value="{{ old('default_code', $product?->default_code) }}" placeholder="Odoo default_code"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="barcode" class="block text-sm font-medium text-brand-ink">Barcode</label>
                <input id="barcode" name="barcode" value="{{ old('barcode', $product?->barcode) }}" placeholder="EAN/UPC barcode"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <div class="md:col-span-3">
                <label for="serial_number" class="block text-sm font-medium text-brand-ink">Representative serial number</label>
                <input id="serial_number" name="serial_number" value="{{ old('serial_number', $product?->serial_number) }}" placeholder="Optional: sample serial for matching"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Warranty Rules</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label for="default_warranty_months" class="block text-sm font-medium text-brand-ink">Default warranty (months)</label>
                <input id="default_warranty_months" type="number" min="1" max="120" name="default_warranty_months"
                    value="{{ old('default_warranty_months', $product?->default_warranty_months) }}" placeholder="12"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label for="registration_grace_days" class="block text-sm font-medium text-brand-ink">Registration grace period (days)</label>
                <input id="registration_grace_days" type="number" min="0" max="365" name="registration_grace_days"
                    value="{{ old('registration_grace_days', $product?->registration_grace_days ?? 30) }}" placeholder="30"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <div class="md:col-span-2">
                <label for="warranty_terms" class="block text-sm font-medium text-brand-ink">Warranty terms</label>
                <textarea id="warranty_terms" name="warranty_terms" rows="4" placeholder="Optional notes shown to internal team or used for terms mapping."
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">{{ old('warranty_terms', $product?->warranty_terms) }}</textarea>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Behavior & Validation</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <label class="inline-flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true))
                    class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
                <span><span class="font-semibold text-brand-ink">Active product</span><span class="mt-0.5 block text-xs text-slate-500">Visible for registration and lookup.</span></span>
            </label>
            <label class="inline-flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                <input type="checkbox" name="serial_tracking_enabled" value="1" @checked(old('serial_tracking_enabled', $product?->serial_tracking_enabled ?? true))
                    class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
                <span><span class="font-semibold text-brand-ink">Serial tracking</span><span class="mt-0.5 block text-xs text-slate-500">Require serial workflows for this product.</span></span>
            </label>
            <label class="inline-flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                <input type="checkbox" name="manual_verification_allowed" value="1" @checked(old('manual_verification_allowed', $product?->manual_verification_allowed ?? true))
                    class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
                <span><span class="font-semibold text-brand-ink">Manual verification allowed</span><span class="mt-0.5 block text-xs text-slate-500">Allow support team to verify manually.</span></span>
            </label>
            <label class="inline-flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                <input type="checkbox" name="receipt_required" value="1" @checked(old('receipt_required', $product?->receipt_required ?? false))
                    class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
                <span><span class="font-semibold text-brand-ink">Receipt required</span><span class="mt-0.5 block text-xs text-slate-500">Ask customer for proof of purchase.</span></span>
            </label>
        </div>
    </section>
</div>
