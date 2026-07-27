<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'K-Elec Warranties')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('home') }}" class="text-xl font-bold text-red-700">K-Elec Warranties</a>
            <nav class="flex items-center gap-4 text-sm font-medium">
                <a href="{{ route('register-warranty.create') }}" class="hover:text-red-700">Register</a>
                <a href="{{ route('warranty.lookup') }}" class="hover:text-red-700">Lookup</a>
                <a href="{{ route('privacy-policy') }}" class="hover:text-red-700">Privacy</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="rounded-md bg-slate-900 px-3 py-2 text-white">Admin</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-md bg-red-700 px-3 py-2 text-white">Staff Login</a>
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

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 md:flex-row md:justify-between">
            <span>&copy; {{ date('Y') }} K-Elec. All rights reserved.</span>
            <div class="flex gap-4">
                <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
                <a href="{{ route('warranty-terms') }}">Warranty Terms</a>
            </div>
        </div>
    </footer>
</body>
</html>
