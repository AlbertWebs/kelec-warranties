<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'K-Elec Warranties') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/png" sizes="32x32" href="https://k-elec.co.ke/favicon/favicon-32x32.png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-brand-ink antialiased bg-brand-soft overflow-y-hidden">
        <div class="brand-accent-bar h-1 w-full fixed top-0 left-0 z-20"></div>

        <div class="h-screen flex flex-col items-center justify-center px-4 py-8">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-5 flex flex-col items-center gap-2">
                    <span class="inline-flex items-center">
                        <x-application-logo class="h-10 w-auto" />
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-navy">Warranty Portal</span>
                </a>

                <div class="w-full rounded-xl border border-gray-200 bg-white px-6 py-6 shadow-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
