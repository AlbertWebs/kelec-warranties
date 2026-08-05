<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access unavailable - {{ config('app.name', 'K-Elec Warranties') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" sizes="32x32" href="https://k-elec.co.ke/favicon/favicon-32x32.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-brand-ink antialiased bg-brand-soft">
    @php
        if (auth()->check()) {
            $homeUrl = route('admin.dashboard');
            $homeLabel = 'Go to dashboard';
        } elseif (auth('customer')->check()) {
            $homeUrl = route('customer.warranties.index');
            $homeLabel = 'Go to my warranties';
        } else {
            $homeUrl = route('home');
            $homeLabel = 'Go to home';
        }
    @endphp

    <div class="brand-accent-bar fixed left-0 top-0 z-20 h-1 w-full"></div>

    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="w-full max-w-md text-center">
            <a href="{{ route('home') }}" class="mb-6 inline-flex flex-col items-center gap-1.5">
                <x-application-logo class="h-9 w-auto sm:h-10" />
                <span class="text-xs font-semibold uppercase tracking-wider text-brand-navy">Warranty Portal</span>
            </a>

            <div class="rounded-xl border border-slate-200 bg-white px-6 py-8 shadow-sm sm:px-8">
                <p class="text-xs font-semibold uppercase tracking-wider text-brand">Access</p>
                <h1 class="mt-2 text-xl font-bold text-brand-ink sm:text-2xl">You don’t have access to this page</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                    This area isn’t available for your account. If you need access, contact your administrator.
                </p>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                    <button
                        type="button"
                        onclick="if (history.length > 1) { history.back(); } else { window.location.href = @js($homeUrl); }"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-brand-ink transition hover:border-brand hover:text-brand"
                    >
                        Go back
                    </button>
                    <a
                        href="{{ $homeUrl }}"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink"
                    >
                        {{ $homeLabel }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
