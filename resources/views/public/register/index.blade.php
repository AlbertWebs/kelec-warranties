@extends('layouts.public')

@section('title', 'Register Warranty')

@section('content')
@php
    $prefill = $prefill ?? [];
    $serialResult = $serialResult ?? session('serial_result');
    $initialStep = 1;

    if ($serialResult || request('serial')) {
        $initialStep = 2;
    }

    if (old('full_name') || old('mobile_number') || old('email')) {
        $initialStep = 3;
    }

    if (old('purchase_source_id') || old('purchase_date') || old('invoice_number') || old('product_name') || old('dealer_id')) {
        $initialStep = 4;
    }

    if (old('privacy_accepted') || old('marketing_consent')) {
        $initialStep = 5;
    }

    if ($errors->any()) {
        if ($errors->hasAny(['purchase_source_id', 'dealer_id', 'branch_name', 'purchase_date', 'invoice_number', 'receipt', 'product_id', 'product_name', 'product_model'])) {
            $initialStep = 4;
        } elseif ($errors->hasAny(['privacy_accepted', 'marketing_consent'])) {
            $initialStep = 5;
        } elseif ($errors->hasAny(['full_name', 'mobile_number', 'email', 'county', 'town'])) {
            $initialStep = 3;
        }
    }

    $wizardPrefill = [
        'serial_number' => old('serial_number', $prefill['serial_number'] ?? request('serial')),
        'product_id' => old('product_id', $prefill['product_id'] ?? ''),
        'product_category_id' => old('product_category_id', $prefill['product_category_id'] ?? ''),
        'product_name' => old('product_name', $prefill['product_name'] ?? ''),
        'product_model' => old('product_model', $prefill['product_model'] ?? ''),
        'purchase_date' => old('purchase_date', $prefill['purchase_date'] ?? ''),
        'invoice_number' => old('invoice_number', $prefill['invoice_number'] ?? ''),
        'dealer_id' => old('dealer_id', $prefill['dealer_id'] ?? ''),
        'branch_name' => old('branch_name', $prefill['branch_name'] ?? ''),
        'purchase_source_id' => old('purchase_source_id', $prefill['purchase_source_id'] ?? ''),
        'purchase_place_label' => old('purchase_place_label', $prefill['purchase_place_label'] ?? ''),
        'full_name' => old('full_name', $prefill['full_name'] ?? ''),
        'mobile_number' => old('mobile_number', $prefill['mobile_number'] ?? ''),
        'email' => old('email', $prefill['email'] ?? ''),
        'county' => old('county', $prefill['county'] ?? ''),
        'town' => old('town', $prefill['town'] ?? ''),
    ];

    $serialValidatedInitially = in_array(($serialResult['status'] ?? ''), ['found', 'found_local'], true);
@endphp

<div class="mx-auto max-w-3xl" x-data="warrantyWizard()">
    @include('public.partials.warranty-tabs', ['activeTab' => 'register'])

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900">Register your K-Elec warranty</h1>
        <p class="mt-2 text-slate-600">Complete the steps below. Serial validation happens first, then your details and consent.</p>
    </div>

    <div class="mb-6 flex gap-2">
        <template x-for="stepNumber in 5" :key="stepNumber">
            <div class="h-2 flex-1 rounded-full" :class="step >= stepNumber ? 'bg-red-700' : 'bg-slate-200'"></div>
        </template>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div x-show="submitSuccess" x-transition class="mb-6 rounded-xl border border-green-200 bg-green-50 p-5 text-green-900">
            <h2 class="text-xl font-semibold">Thank you! Your registration was submitted.</h2>
            <p class="mt-2 text-sm">Reference: <span class="font-semibold" x-text="submitData.reference"></span></p>
            <p class="mt-1 text-sm">A confirmation message has been sent to your mobile number, and to your email if provided.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a :href="submitData.next_url" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">View summary</a>
                <a :href="submitData.lookup_url" class="rounded-lg border border-green-300 bg-white px-4 py-2 text-sm font-semibold text-green-800">Lookup warranty</a>
                <a :href="submitData.certificate_url" class="rounded-lg border border-green-300 bg-white px-4 py-2 text-sm font-semibold text-green-800">View certificate</a>
            </div>
        </div>

        <div x-show="submitError" x-transition class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="submitError"></div>

        <div x-show="!submitSuccess">
        <div x-show="step === 1" x-transition.opacity.duration.200ms>
            <h2 class="text-xl font-semibold">Step 1: Serial number</h2>
            <p class="mt-2 text-sm text-slate-600">Find the serial number on the product rating label, packaging, or invoice.</p>
            <form class="mt-6 space-y-4" @submit.prevent="validateSerial">
                <div>
                    <label class="mb-1 block text-sm font-medium">Product serial number</label>
                    <div class="flex gap-2">
                        <input type="text" id="serial_number" x-model="serialNumber" :disabled="validatingSerial" required
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-600 focus:ring-red-600 disabled:bg-slate-50"
                               placeholder="e.g. KE123456789" autocomplete="off">
                        <button type="button" id="scan-serial-btn" :disabled="validatingSerial" class="whitespace-nowrap rounded-lg border px-3 py-2 text-sm disabled:opacity-60">Scan QR</button>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Tip: Brand Shop QR codes open this page with your serial prefilled. On supported phones, use Scan QR.</p>
                    <video id="serial-scanner" class="mt-3 hidden max-h-48 w-full rounded-lg bg-black" playsinline></video>
                </div>

                <div x-show="serialError" x-cloak x-transition class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="serialError"></div>

                <button type="submit" :disabled="validatingSerial || !serialNumber.trim()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 font-semibold text-white disabled:opacity-60">
                    <svg x-show="validatingSerial" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" class="opacity-20" stroke="currentColor" stroke-width="3"></circle>
                        <path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" class="opacity-90"></path>
                    </svg>
                    <span x-text="validatingSerial ? 'Validating serial...' : 'Validate serial'"></span>
                </button>
            </form>
        </div>

        <div x-show="step === 2" x-cloak x-transition.opacity.duration.200ms>
            <h2 class="text-xl font-semibold">Step 2: Validation result</h2>

            <div class="mt-4 rounded-xl border px-4 py-3 text-sm"
                 :class="serialValidated
                    ? (saleStatus === 'in_stock' ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-green-200 bg-green-50 text-green-800')
                    : 'border-amber-200 bg-amber-50 text-amber-900'">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                         :class="serialValidated && saleStatus !== 'in_stock' ? 'bg-green-600 text-white' : 'bg-amber-500 text-white'">
                        <svg x-show="serialValidated && saleStatus !== 'in_stock'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <svg x-show="!serialValidated || saleStatus === 'in_stock'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold" x-text="saleStatus === 'in_stock' ? 'In stock, not sold yet' : (serialValidated ? 'Serial validated' : 'Manual verification needed')"></p>
                        <p class="mt-1" x-text="serialMessage"></p>
                        <div x-show="saleStatus === 'in_stock'" x-cloak class="mt-3 flex flex-wrap gap-2">
                            <a href="https://k-elec.co.ke/brand-shops" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                                Brand Shops
                                <span aria-hidden="true">↗</span>
                            </a>
                            <a href="{{ route('find-store') }}"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-semibold text-amber-900 hover:border-brand hover:text-brand">
                                Outlets
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2" x-show="saleStatus !== 'in_stock' && (serialPrefill.product_name || serialPrefill.product_model || serialPrefill.purchase_date || serialPrefill.invoice_number || serialPrefill.purchase_place_label)">
                <div>
                    <dt class="text-slate-500">Product</dt>
                    <dd class="font-medium text-slate-900" x-text="serialPrefill.product_name || '—'"></dd>
                </div>
                <div>
                    <dt class="text-slate-500">Model</dt>
                    <dd class="font-medium text-slate-900" x-text="serialPrefill.product_model || '—'"></dd>
                </div>
                <div>
                    <dt class="text-slate-500">Place of purchase</dt>
                    <dd class="font-medium text-slate-900" x-text="serialPrefill.purchase_place_label || serialPrefill.branch_name || '—'"></dd>
                </div>
                <div>
                    <dt class="text-slate-500">Purchase date</dt>
                    <dd class="font-medium text-slate-900" x-text="formatDate(serialPrefill.purchase_date)"></dd>
                </div>
                <div>
                    <dt class="text-slate-500">Invoice</dt>
                    <dd class="font-medium text-slate-900" x-text="serialPrefill.invoice_number || '—'"></dd>
                </div>
            </dl>

            <div class="mt-6 flex gap-3">
                <button type="button" class="rounded-lg border px-4 py-2" @click="step = 1">Back</button>
                <button type="button" x-show="saleStatus !== 'in_stock'" class="rounded-lg bg-red-700 px-4 py-2 text-white" @click="continueAfterSerial">Continue</button>
            </div>
        </div>

        <form method="POST" action="{{ route('register-warranty.store') }}" enctype="multipart/form-data" x-show="step >= 3" @submit.prevent="submitRegistration($event)">
            @csrf
            <input type="hidden" name="serial_number" :value="serialNumber">
            <input type="hidden" name="product_id" :value="serialPrefill.product_id || ''">
            <input type="hidden" name="product_category_id" :value="serialPrefill.product_category_id || ''">

            <div x-show="step === 3">
                <h2 class="text-xl font-semibold">Step 3: Customer details</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Full name</label>
                        <input name="full_name" x-model="serialPrefill.full_name" value="{{ old('full_name', $prefill['full_name'] ?? '') }}" required class="w-full rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Mobile number</label>
                        <input name="mobile_number" x-model="serialPrefill.mobile_number" value="{{ old('mobile_number', $prefill['mobile_number'] ?? '') }}" required class="w-full rounded-lg border-slate-300" placeholder="07XXXXXXXX">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Email (optional)</label>
                        <input type="email" name="email" x-model="serialPrefill.email" value="{{ old('email', $prefill['email'] ?? '') }}" class="w-full rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">County (optional)</label>
                        <input name="county" x-model="serialPrefill.county" value="{{ old('county', $prefill['county'] ?? '') }}" class="w-full rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Town / location (optional)</label>
                        <input name="town" x-model="serialPrefill.town" value="{{ old('town', $prefill['town'] ?? '') }}" class="w-full rounded-lg border-slate-300">
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" class="rounded-lg border px-4 py-2" @click="step = 2">Back</button>
                    <button type="button" class="rounded-lg bg-red-700 px-4 py-2 text-white" @click="step = 4">Continue</button>
                </div>
            </div>

            <div x-show="step === 4">
                <h2 class="text-xl font-semibold">Step 4: Purchase details</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Place of purchase</label>
                        <select name="purchase_source_id" x-model="purchaseSourceId" required class="w-full rounded-lg border-slate-300">
                            <option value="">Select source</option>
                            @foreach ($purchaseSources as $source)
                                <option value="{{ $source->id }}" @selected(old('purchase_source_id', $prefill['purchase_source_id'] ?? null) == $source->id)>{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="isBrandShopSelected" x-cloak>
                        <label class="mb-1 block text-sm font-medium">Brand Shop</label>
                        <select name="branch_name" x-model="serialPrefill.branch_name" class="w-full rounded-lg border-slate-300" :disabled="!isBrandShopSelected">
                            <option value="">Select Brand Shop</option>
                            @foreach (($brandShops ?? []) as $shop)
                                <option value="{{ $shop }}">{{ $shop }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="isDealerSelected" x-cloak>
                        <label class="mb-1 block text-sm font-medium">Dealer</label>
                        <select name="dealer_id" x-model="dealerId" class="w-full rounded-lg border-slate-300" :disabled="!isDealerSelected">
                            <option value="">Select dealer</option>
                            @foreach ($dealers as $dealer)
                                <option value="{{ $dealer->id }}" @selected(old('dealer_id', $prefill['dealer_id'] ?? null) == $dealer->id)>{{ $dealer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="!isBrandShopSelected && !isDealerSelected" x-cloak>
                        <label class="mb-1 block text-sm font-medium">Branch / seller name</label>
                        <input x-model="serialPrefill.branch_name" :name="(!isBrandShopSelected && !isDealerSelected) ? 'branch_name' : ''" value="{{ old('branch_name', $prefill['branch_name'] ?? '') }}" class="w-full rounded-lg border-slate-300" placeholder="Outlet or seller name" :disabled="isBrandShopSelected || isDealerSelected">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Purchase date</label>
                        <input type="date" name="purchase_date" x-model="serialPrefill.purchase_date" value="{{ old('purchase_date', $prefill['purchase_date'] ?? '') }}" required class="w-full rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Invoice / receipt number</label>
                        <input name="invoice_number" x-model="serialPrefill.invoice_number" value="{{ old('invoice_number', $prefill['invoice_number'] ?? '') }}" class="w-full rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Receipt upload (PDF/JPG/PNG)</label>
                        <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Product (if not auto-filled)</label>
                        <select name="product_id" class="w-full rounded-lg border-slate-300"
                            x-model="serialPrefill.product_id"
                            @change="serialPrefill.product_id = $event.target.value">
                            <option value="">Select product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id', $prefill['product_id'] ?? null) == $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Product name (manual)</label>
                        <input name="product_name" x-model="serialPrefill.product_name" value="{{ old('product_name', $prefill['product_name'] ?? '') }}" class="w-full rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Product model</label>
                        <input name="product_model" x-model="serialPrefill.product_model" value="{{ old('product_model', $prefill['product_model'] ?? '') }}" class="w-full rounded-lg border-slate-300">
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" class="rounded-lg border px-4 py-2" @click="step = 3">Back</button>
                    <button type="button" class="rounded-lg bg-red-700 px-4 py-2 text-white" @click="step = 5">Continue</button>
                </div>
            </div>

            <div x-show="step === 5">
                <h2 class="text-xl font-semibold">Step 5: Consent and submission</h2>
                <div class="mt-4 space-y-4 rounded-lg bg-slate-50 p-4 text-sm">
                    <p>Serial: <strong x-text="serialNumber || '—'"></strong></p>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="privacy_accepted" value="1" required class="mt-1 rounded border-slate-300 text-red-700 focus:ring-red-600" @checked(old('privacy_accepted'))>
                        <span>I accept the <a href="{{ route('privacy-policy') }}" class="text-red-700 underline" target="_blank">Privacy Policy</a> and <a href="{{ route('warranty-terms') }}" class="text-red-700 underline" target="_blank">Warranty Terms</a>.</span>
                    </label>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="marketing_consent" value="1" class="mt-1 rounded border-slate-300 text-red-700 focus:ring-red-600" @checked(old('marketing_consent'))>
                        <span>I would like to receive marketing communication from K-Elec (optional, unticked by default).</span>
                    </label>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" class="rounded-lg border px-4 py-2" @click="step = 4">Back</button>
                    <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white disabled:opacity-70" :disabled="submitting">
                        <span x-show="!submitting">Submit registration</span>
                        <span x-show="submitting">Submitting...</span>
                    </button>
                </div>
            </div>
        </form>
        </div>
    </div>
</div>

<script>
function warrantyWizard() {
    const initialPrefill = @json($wizardPrefill);
    const initialSerialResult = @json($serialResult);

    return {
        step: {{ (int) $initialStep }},
        submitting: false,
        submitSuccess: false,
        submitError: '',
        submitData: {},
        serialNumber: initialPrefill.serial_number || '',
        validatingSerial: false,
        serialError: '',
        serialValidated: @json($serialValidatedInitially),
        serialMessage: initialSerialResult?.message || '',
        saleStatus: initialPrefill.sale_status || initialSerialResult?.odoo?.sale?.sale_status || '',
        purchaseSourceId: initialPrefill.purchase_source_id ? String(initialPrefill.purchase_source_id) : '',
        dealerId: initialPrefill.dealer_id ? String(initialPrefill.dealer_id) : '',
        brandShopSourceId: @json(isset($brandShopSourceId) ? (string) $brandShopSourceId : ''),
        dealerSourceId: @json(isset($dealerSourceId) ? (string) $dealerSourceId : ''),
        get isBrandShopSelected() {
            return String(this.purchaseSourceId || '') === String(this.brandShopSourceId || '') && this.brandShopSourceId !== '';
        },
        get isDealerSelected() {
            return String(this.purchaseSourceId || '') === String(this.dealerSourceId || '') && this.dealerSourceId !== '';
        },
        serialPrefill: {
            product_id: initialPrefill.product_id ? String(initialPrefill.product_id) : '',
            product_category_id: initialPrefill.product_category_id ? String(initialPrefill.product_category_id) : '',
            product_name: initialPrefill.product_name || '',
            product_model: initialPrefill.product_model || '',
            purchase_date: initialPrefill.purchase_date || '',
            invoice_number: initialPrefill.invoice_number || '',
            branch_name: initialPrefill.branch_name || '',
            purchase_place_label: initialPrefill.purchase_place_label || '',
            full_name: initialPrefill.full_name || '',
            mobile_number: initialPrefill.mobile_number || '',
            email: initialPrefill.email || '',
            county: initialPrefill.county || '',
            town: initialPrefill.town || '',
        },
        formatDate(value) {
            if (!value) return '—';
            const date = new Date(`${value}T00:00:00`);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },
        applyPrefill(prefill = {}) {
            const next = { ...this.serialPrefill };
            for (const [key, value] of Object.entries(prefill)) {
                if (value === null || value === undefined || value === '') continue;
                if (key === 'serial_number') {
                    this.serialNumber = String(value);
                    continue;
                }
                if (key === 'purchase_source_id') {
                    this.purchaseSourceId = String(value);
                    continue;
                }
                if (key === 'dealer_id') {
                    this.dealerId = String(value);
                    continue;
                }
                if (Object.prototype.hasOwnProperty.call(next, key)) {
                    next[key] = String(value);
                }
            }
            this.serialPrefill = next;
            this.syncProductSelect();
        },
        syncProductSelect() {
            const productSelect = document.querySelector('select[name="product_id"]');
            if (!productSelect) return;

            if (this.serialPrefill.product_id) {
                productSelect.value = String(this.serialPrefill.product_id);
                return;
            }

            if (!this.serialPrefill.product_name) return;
            const target = this.serialPrefill.product_name.trim().toLowerCase();
            const match = Array.from(productSelect.options).find(opt => opt.textContent.trim().toLowerCase() === target);
            if (match) {
                productSelect.value = match.value;
                this.serialPrefill.product_id = match.value;
            }
        },
        continueAfterSerial() {
            if (this.saleStatus === 'in_stock') {
                return;
            }
            this.syncProductSelect();
            this.step = 3;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        async validateSerial() {
            const serial = (this.serialNumber || '').trim();
            if (!serial || this.validatingSerial) return;

            this.validatingSerial = true;
            this.serialError = '';

            try {
                const response = await fetch(@json(route('register-warranty.serial-check')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            || document.querySelector('input[name="_token"]')?.value
                            || '',
                    },
                    body: JSON.stringify({ serial_number: serial }),
                });

                const payload = await response.json();

                if (response.status === 409 && payload.status === 'existing_active') {
                    if (payload.redirect_url) {
                        window.location.href = payload.redirect_url;
                        return;
                    }
                    this.serialError = payload.message || 'This serial already has an active warranty.';
                    return;
                }

                if (!response.ok) {
                    const firstError = payload?.errors ? Object.values(payload.errors)[0] : null;
                    this.serialError = Array.isArray(firstError)
                        ? firstError[0]
                        : (payload.message || 'Unable to validate this serial number right now.');
                    return;
                }

                this.serialNumber = (payload.prefill?.serial_number || serial).toUpperCase();
                this.serialValidated = Boolean(payload.validated);
                this.serialMessage = payload.message || '';
                this.saleStatus = payload.sale_status || payload.prefill?.sale_status || '';
                this.applyPrefill(payload.prefill || {});
                this.step = 2;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (e) {
                this.serialError = 'We could not validate the serial number right now. Please try again shortly.';
            } finally {
                this.validatingSerial = false;
            }
        },
        async submitRegistration(event) {
            if (this.submitting) return;
            this.submitting = true;
            this.submitError = '';

            const form = event.target;
            const formData = new FormData(form);
            formData.set('serial_number', this.serialNumber);
            if (this.serialPrefill.product_id) {
                formData.set('product_id', this.serialPrefill.product_id);
            }
            if (this.serialPrefill.product_category_id) {
                formData.set('product_category_id', this.serialPrefill.product_category_id);
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    if (payload?.errors) {
                        const keys = Object.keys(payload.errors);
                        if (keys.some(k => ['purchase_source_id','dealer_id','branch_name','purchase_date','invoice_number','receipt','product_id','product_name','product_model'].includes(k))) {
                            this.step = 4;
                        } else if (keys.some(k => ['privacy_accepted','marketing_consent'].includes(k))) {
                            this.step = 5;
                        } else if (keys.some(k => ['full_name','mobile_number','email','county','town'].includes(k))) {
                            this.step = 3;
                        }
                        const firstError = Object.values(payload.errors)[0];
                        this.submitError = Array.isArray(firstError) ? firstError[0] : 'Please review your form details and try again.';
                    } else {
                        this.submitError = payload.message || 'Unable to submit registration. Please review your details and try again.';
                    }
                    this.submitting = false;
                    return;
                }

                this.submitData = payload;
                this.submitSuccess = true;
                this.step = 5;
                try {
                    localStorage.removeItem('warranty_registration_draft_v1');
                } catch (e) {}
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (e) {
                this.submitError = 'We could not submit your registration right now. Please try again shortly.';
            } finally {
                this.submitting = false;
            }
        }
    }
}

document.getElementById('scan-serial-btn')?.addEventListener('click', async () => {
    const video = document.getElementById('serial-scanner');
    const input = document.getElementById('serial_number');
    if (!('BarcodeDetector' in window)) {
        alert('QR scanning is not supported on this browser. Please enter the serial number manually.');
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        video.srcObject = stream;
        video.classList.remove('hidden');
        await video.play();
        const detector = new BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39'] });
        const scan = async () => {
            try {
                const codes = await detector.detect(video);
                if (codes.length > 0) {
                    let value = codes[0].rawValue || '';
                    try {
                        const url = new URL(value);
                        value = url.searchParams.get('serial') || url.searchParams.get('reference') || value;
                    } catch (e) {}
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    stream.getTracks().forEach(track => track.stop());
                    video.classList.add('hidden');
                    return;
                }
            } catch (e) {}
            requestAnimationFrame(scan);
        };
        scan();
    } catch (e) {
        alert('Unable to access the camera for scanning.');
    }
});

(() => {
    const DRAFT_KEY = 'warranty_registration_draft_v1';

    const allFields = () => Array.from(document.querySelectorAll(
        'input[name], select[name], textarea[name]'
    ));

    const saveDraft = () => {
        const draft = {};
        for (const el of allFields()) {
            const name = el.getAttribute('name');
            if (!name) continue;

            if (el.type === 'checkbox') {
                draft[name] = !!el.checked;
            } else if (el.type !== 'file') {
                draft[name] = el.value ?? '';
            }
        }

        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
        } catch (e) {}
    };

    const restoreDraft = () => {
        let raw;
        try {
            raw = localStorage.getItem(DRAFT_KEY);
        } catch (e) {
            return;
        }
        if (!raw) return;

        let draft;
        try {
            draft = JSON.parse(raw);
        } catch (e) {
            return;
        }

        for (const el of allFields()) {
            const name = el.getAttribute('name');
            if (!name || !(name in draft)) continue;

            if (el.type === 'checkbox') {
                // Keep server-side old() truthy values if already checked.
                if (!el.checked) {
                    el.checked = !!draft[name];
                }
                continue;
            }

            if (el.type === 'file') continue;

            // Preserve server-sent old()/prefill values when present.
            if (!el.value) {
                el.value = draft[name] ?? '';
            }
        }
    };

    restoreDraft();

    // If product was fetched by query result but product_id is empty, auto-select matching product by name.
    const productSelect = document.querySelector('select[name="product_id"]');
    const productNameInput = document.querySelector('input[name="product_name"]');
    const hiddenProductId = document.querySelector('input[type="hidden"][name="product_id"]');
    if (productSelect && productNameInput && hiddenProductId && !hiddenProductId.value && productNameInput.value) {
        const target = productNameInput.value.trim().toLowerCase();
        const match = Array.from(productSelect.options).find(opt => opt.textContent.trim().toLowerCase() === target);
        if (match) {
            productSelect.value = match.value;
            hiddenProductId.value = match.value;
        }
    }

    const throttledSave = (() => {
        let timer = null;
        return () => {
            if (timer) window.clearTimeout(timer);
            timer = window.setTimeout(saveDraft, 150);
        };
    })();

    document.addEventListener('input', throttledSave);
    document.addEventListener('change', throttledSave);

    // Clear draft after successful submission redirect.
    if (window.location.pathname.includes('/register-warranty/success/')) {
        try {
            localStorage.removeItem(DRAFT_KEY);
        } catch (e) {}
    }
})();
</script>
@endsection
