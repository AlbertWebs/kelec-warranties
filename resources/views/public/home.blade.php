@extends('layouts.public')

@section('title', 'Register and Track Your Appliance Warranty with Confidence | K-Elec Kenya')
@section('meta_description', 'Register and track your appliance warranty with confidence on the K-Elec Kenya warranty portal. Activate cover, lookup warranty status, and download certificates securely.')
@section('meta_keywords', 'register appliance warranty, track appliance warranty, K-Elec warranty Kenya, warranty registration portal, warranty lookup, warranty certificate download')
@section('canonical_url', url('/'))
@section('og_title', 'Register and Track Your Appliance Warranty with Confidence')
@section('og_description', 'Use the official K-Elec warranty portal to register appliances, track warranty status, and access digital warranty certificates.')
@section('og_image', 'https://k-elec.co.ke/storage/brand-shops/FyNQsy123XOFdAiBOCzjzasevNe2bg292ToKAcyd.jpg')
@section('og_image_alt', 'K-Elec showroom with Google TV displays — Korean Technology Company, 3 year warranty, proudly made in Kenya')

@push('head')
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "WebSite",
            "name": "K-Elec Warranty Portal",
            "url": "{{ url('/') }}",
            "description": "Register and track your appliance warranty with confidence.",
            "potentialAction": {
                "@@type": "SearchAction",
                "target": "{{ route('warranty.lookup') }}?reference={warranty_reference}",
                "query-input": "required name=warranty_reference"
            }
        }
    </script>
@endpush

@section('content')
<section class="overflow-hidden rounded-3xl border border-slate-200 bg-[#f5f7ff] px-5 py-8 shadow-sm sm:px-8 lg:px-10">
    <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
        <div>
            <p class="inline-flex rounded-full border border-brand/15 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-brand">
                K-Elec · Korean Tech, Kenyan Trust
            </p>
            <h1 class="mt-4 text-4xl font-bold tracking-tight text-brand-ink sm:text-5xl">
                Register and track your appliance <span class="text-brand">warranty</span> with confidence
            </h1>
            <p class="mt-4 max-w-xl text-base leading-relaxed text-slate-600">
                Register and track your appliance warranty with confidence in minutes. Enter your product details to activate a new warranty, check coverage status, and keep your proof of warranty ready for support.
            </p>

            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                <a href="{{ route('register-warranty.create') }}" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow">
                    <div class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-light text-brand">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <p class="font-semibold text-brand-ink">Register New</p>
                    <p class="mt-1 text-sm text-slate-600">Protect your new K-Elec purchase today.</p>
                    <p class="mt-3 text-sm font-semibold text-brand">Get Started</p>
                </a>
                <a href="{{ route('product.lookup') }}" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow">
                    <div class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-soft text-brand-navy">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.15a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                        </svg>
                    </div>
                    <p class="font-semibold text-brand-ink">Lookup Product</p>
                    <p class="mt-1 text-sm text-slate-600">Search by serial number, barcode, SKU, or name.</p>
                    <p class="mt-3 text-sm font-semibold text-brand-navy">Find Product</p>
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-brand/10 bg-white p-5 shadow-lg shadow-brand/10">
            <div class="mb-4 inline-flex rounded-md bg-brand px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-white">
                Warranty confidence
            </div>
            <h2 class="text-xl font-bold text-brand-ink">Built for real K-Elec customers</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Register your appliance once, track warranty status anytime, and keep your certificate ready when you need support.
            </p>

            <div class="mt-5 space-y-3">
                <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-light text-brand">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-brand-ink">Fast registration</p>
                        <p class="text-xs text-slate-600">Complete registration in a few minutes using serial number, purchase date, and mobile number.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand-navy">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.15a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-brand-ink">Secure lookup</p>
                        <p class="text-xs text-slate-600">Use your reference and registered mobile number to check coverage and warranty status securely.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25L15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-brand-ink">Digital certificate access</p>
                        <p class="text-xs text-slate-600">View or download your warranty certificate whenever you need claim or service support.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mt-14 rounded-3xl border border-slate-100 bg-white px-5 py-10 sm:px-8">
    <div class="mx-auto max-w-3xl text-center">
        <h2 class="text-3xl font-bold tracking-tight text-brand-ink">What you will need</h2>
        <p class="mt-2 text-sm text-slate-500">
            Please have these details ready to ensure a smooth registration or lookup process. All
            information is handled securely according to our privacy policy.
        </p>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#f7f9ff] text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5A2.25 2.25 0 006.75 19.5h7.5a2.25 2.25 0 002.25-2.25V16.5m-9-9h7.5a2.25 2.25 0 012.25 2.25v7.5m-9-9l9 9" />
                </svg>
            </div>
            <p class="mt-3 text-[15px] font-semibold text-brand-ink">Serial Number</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Found on the back or bottom of your appliance, on a silver or white sticker.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#f7f9ff] text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V4m8 3V4m-9 8h10M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="mt-3 text-[15px] font-semibold text-brand-ink">Purchase Date</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">As printed on your official tax invoice or electronic receipt.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#f7f9ff] text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8.25 3h7.5A2.25 2.25 0 0118 5.25v13.5A2.25 2.25 0 0115.75 21h-7.5A2.25 2.25 0 016 18.75V5.25A2.25 2.25 0 018.25 3z" />
                </svg>
            </div>
            <p class="mt-3 text-[15px] font-semibold text-brand-ink">Mobile Number</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Required for SMS verification and digital warranty certificate delivery.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#f7f9ff] text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5l1.5-4.5h15L21 10.5M4.5 10.5h15v8.25A2.25 2.25 0 0117.25 21H6.75A2.25 2.25 0 014.5 18.75V10.5zm4.5 3.75h6" />
                </svg>
            </div>
            <p class="mt-3 text-[15px] font-semibold text-brand-ink">Retail Outlet</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">The name and branch of the authorized K-Elec partner where you bought the unit.</p>
        </div>
    </div>

    <div class="mt-6 flex items-start gap-3 rounded-xl border border-[#d7e4ff] bg-[#ecf2ff] px-4 py-3 text-sm text-slate-600">
        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-[#c1d6ff] text-[11px] text-brand-navy">i</span>
        <p>
            <span class="font-semibold text-brand-navy">Pro Tip:</span> Marketing consent is entirely optional and never required to activate your warranty.
            We value your choice to receive updates or stick to essential service alerts.
        </p>
    </div>
</section>

<section class="mt-10 rounded-2xl border border-[#d9e4ff] bg-gradient-to-br from-[#f8fbff] to-white p-5 shadow-sm">
    <div class="mb-4">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand/70">Why Customers Choose K-Elec</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#eef4ff] text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7.5 3v5.25c0 5.1-3.45 9.3-7.5 10.5-4.05-1.2-7.5-5.4-7.5-10.5V6L12 3z" />
                </svg>
            </span>
            <p class="mt-3 text-[15px] font-semibold text-brand-ink">Bank-Grade Security</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Your registration details are encrypted in transit and at rest to keep personal data protected.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#eef4ff] text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <p class="mt-3 text-[15px] font-semibold text-brand-ink">Fast, Verified Activation</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Register in minutes and receive a verified digital warranty certificate by email right away.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#eef4ff] text-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2h2m10 0V6a5 5 0 00-10 0v2m10 0H7" />
                </svg>
            </span>
            <p class="mt-3 text-[15px] font-semibold text-brand-ink">Real People, Real Support</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Our support team helps resolve claims, disputes, and warranty questions quickly and clearly.</p>
        </div>
    </div>
</section>
@endsection
