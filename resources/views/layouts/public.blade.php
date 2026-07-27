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
<body class="bg-brand-soft text-brand-ink antialiased">
    <div class="brand-accent-bar h-1 w-full"></div>
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-brand text-sm font-bold text-white">KE</span>
                <span class="leading-tight">
                    <span class="block text-lg font-bold text-brand">K-Elec</span>
                    <span class="block text-xs font-medium uppercase tracking-wide text-brand-navy">Warranties</span>
                </span>
            </a>
            <nav class="flex items-center gap-4 text-sm font-medium text-brand-ink">
                <a href="{{ route('register-warranty.create') }}" class="hover:text-brand">Register</a>
                <a href="{{ route('warranty.lookup') }}" class="hover:text-brand">Lookup</a>
                <a href="{{ route('privacy-policy') }}" class="hover:text-brand">Privacy</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="rounded-md bg-brand-navy px-3 py-2 text-white hover:bg-brand-ink">Admin</a>
                @else
                    <a href="{{ route('login') }}" class="btn-brand">Staff Login</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
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

    <footer class="border-t border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-gray-500 md:flex-row md:items-center md:justify-between">
            <div>
                <span class="font-semibold text-brand-navy">K-Elec</span>
                <span> · Korean Tech, Kenyan Trust</span>
                <div class="mt-1">&copy; {{ date('Y') }} K-Elec. All rights reserved.</div>
            </div>
            <div class="flex flex-col gap-1 md:items-end">
                <a href="tel:+254716052243" class="hover:text-brand">0716 052 243</a>
                <div class="flex gap-4">
                    <a href="{{ route('privacy-policy') }}" class="hover:text-brand">Privacy Policy</a>
                    <a href="{{ route('warranty-terms') }}" class="hover:text-brand">Warranty Terms</a>
                    <a href="https://k-elec.co.ke/" class="hover:text-brand" target="_blank" rel="noopener">k-elec.co.ke</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
