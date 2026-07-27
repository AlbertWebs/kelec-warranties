<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'K-Elec Warranties')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />
    <link rel="icon" type="image/png" sizes="32x32" href="https://k-elec.co.ke/favicon/favicon-32x32.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-brand-soft text-brand-ink antialiased" x-data="{ mobileNavOpen: false }" @keydown.escape.window="mobileNavOpen = false">
    <div class="brand-accent-bar h-1 w-full shrink-0"></div>
    <header class="sticky top-0 z-50 shrink-0 border-b border-gray-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-2.5 sm:py-3">
            <a href="{{ route('home') }}" class="inline-flex shrink-0 items-center">
                <x-application-logo class="h-8 w-auto sm:h-9" />
            </a>

            <nav class="hidden items-center gap-1 text-sm font-medium text-brand-ink md:flex">
                <a href="https://k-elec.co.ke/brand-shops" target="_blank" rel="noopener" class="rounded-md px-3 py-2 hover:bg-brand-soft hover:text-brand">Brand Shops</a>
                <a href="https://k-elec.co.ke/brand-shops" target="_blank" rel="noopener" class="rounded-md px-3 py-2 hover:bg-brand-soft hover:text-brand">Our Outlets</a>
                <a href="https://k-elec.co.ke/" target="_blank" rel="noopener" class="rounded-md px-3 py-2 hover:bg-brand-soft hover:text-brand">Home</a>
                @auth('customer')
                    <a href="{{ route('customer.warranties.index') }}" class="rounded-md px-3 py-2 hover:bg-brand-soft hover:text-brand">My Warranties</a>
                    <a href="{{ route('customer.claims.index') }}" class="rounded-md px-3 py-2 hover:bg-brand-soft hover:text-brand">Claims</a>
                    <form method="POST" action="{{ route('customer.logout') }}" class="ml-1">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-brand-navy px-3 py-2 text-white hover:bg-brand-ink">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('customer.login') }}" class="btn-brand ml-1 !px-3 !py-2 text-sm">Customer Login</a>
                @endauth
                @auth('web')
                    <a href="{{ route('admin.dashboard') }}" class="ml-1 rounded-md border border-brand-navy px-3 py-2 text-brand-navy hover:bg-brand-soft">Admin</a>
                @endauth
            </nav>

            <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-gray-200 text-brand-ink transition hover:border-brand hover:text-brand md:hidden"
                    @click="mobileNavOpen = !mobileNavOpen"
                    :aria-expanded="mobileNavOpen.toString()"
                    aria-controls="mobile-nav"
                    aria-label="Toggle menu">
                <svg x-show="!mobileNavOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
                <svg x-show="mobileNavOpen" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div id="mobile-nav"
             x-show="mobileNavOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="border-t border-gray-100 bg-white md:hidden"
             @click.outside="mobileNavOpen = false">
            <nav class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3 text-sm font-medium text-brand-ink">
                <a href="https://k-elec.co.ke/brand-shops" target="_blank" rel="noopener" class="rounded-lg px-3 py-3 hover:bg-brand-soft hover:text-brand">Brand Shops ↗</a>
                <a href="https://k-elec.co.ke/brand-shops" target="_blank" rel="noopener" class="rounded-lg px-3 py-3 hover:bg-brand-soft hover:text-brand">Our Outlets ↗</a>
                <a href="https://k-elec.co.ke/" target="_blank" rel="noopener" class="rounded-lg px-3 py-3 hover:bg-brand-soft hover:text-brand">Home ↗</a>
                <div class="my-1 border-t border-gray-100"></div>
                @auth('customer')
                    <a href="{{ route('customer.warranties.index') }}" class="rounded-lg px-3 py-3 hover:bg-brand-soft hover:text-brand" @click="mobileNavOpen = false">My Warranties</a>
                    <a href="{{ route('customer.claims.index') }}" class="rounded-lg px-3 py-3 hover:bg-brand-soft hover:text-brand" @click="mobileNavOpen = false">Claims</a>
                    <form method="POST" action="{{ route('customer.logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-navy px-3 py-3 text-center text-white">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('customer.login') }}" class="btn-brand text-center" @click="mobileNavOpen = false">Customer Login</a>
                @endauth
                @auth('web')
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-brand-navy px-3 py-3 text-center text-brand-navy" @click="mobileNavOpen = false">Admin dashboard</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
        @if (session('success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">{{ session('warning') }}</div>
        @endif
        @if (session('status'))
            <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-blue-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="relative mt-auto shrink-0 overflow-hidden bg-brand-ink text-white">
        <div class="brand-accent-bar h-1 w-full"></div>
        <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-brand/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-brand-navy/40 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-6xl gap-8 px-4 py-10 md:grid-cols-3">
            <div class="md:col-span-1">
                <div class="flex items-center gap-3">
                    <span class="inline-flex shrink-0 items-center">
                        <x-application-logo class="h-9 w-auto" />
                    </span>
                    <div class="leading-tight">
                        <div class="text-sm font-semibold uppercase tracking-wider text-white/70">Warranties</div>
                        <div class="text-xs text-white/45">Portal</div>
                    </div>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-white/70">
                    Korean Tech, Kenyan Trust — register and look up your appliance warranty in minutes.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('register-warranty.create') }}"
                       class="footer-cta inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-dark hover:shadow-lg hover:shadow-brand/25">
                        Register a warranty
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="https://k-elec.co.ke/" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-lg border border-white/20 px-4 py-2.5 text-sm font-semibold text-white transition hover:border-white/40 hover:bg-white/5">
                        Main website
                        <span aria-hidden="true" class="text-white/60">↗</span>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-white/50">Quick links</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('register-warranty.create') }}" class="footer-link">Register warranty</a></li>
                    <li><a href="{{ route('warranty.lookup') }}" class="footer-link">Lookup warranty</a></li>
                    <li><a href="https://k-elec.co.ke/brand-shops" class="footer-link" target="_blank" rel="noopener">Brand Shops / Outlets</a></li>
                    <li><a href="https://k-elec.co.ke/" class="footer-link" target="_blank" rel="noopener">Main website (k-elec.co.ke)</a></li>
                    <li><a href="{{ route('privacy-policy') }}" class="footer-link">Privacy Policy</a></li>
                    <li><a href="{{ route('warranty-terms') }}" class="footer-link">Warranty Terms</a></li>
                    <li><a href="{{ route('login') }}" class="footer-link">Staff Login</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-white/50">Support</h3>
                <ul class="mt-3 space-y-3 text-sm text-white/80">
                    <li>
                        <div class="text-white/50">Phone</div>
                        <a href="tel:+254716052243" class="footer-link text-base font-medium text-white">0716 052 243</a>
                    </li>
                    <li>
                        <div class="text-white/50">Brand shops</div>
                        <div>Sarin · CBD · Westlands</div>
                    </li>
                    <li>
                        <div class="text-white/50">Hours</div>
                        <div>Mon–Sat, 9AM–6PM</div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="relative border-t border-white/10">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-4 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between">
                <span>&copy; {{ date('Y') }} K-Elec. All rights reserved.</span>
                <span class="footer-pulse inline-flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand"></span>
                    Secure warranty portal
                </span>
            </div>
        </div>
    </footer>
</body>
</html>
