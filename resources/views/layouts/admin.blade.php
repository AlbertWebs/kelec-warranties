<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') - K-Elec Warranties</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />
    <link rel="icon" type="image/png" sizes="32x32" href="https://k-elec.co.ke/favicon/favicon-32x32.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-soft text-brand-ink antialiased" x-data="{ sidebarOpen: false }">
<div class="min-h-screen md:flex">
    <aside class="fixed inset-y-0 left-0 z-40 w-72 transform bg-brand-ink text-slate-100 transition md:static md:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
        <div class="flex h-16 items-center gap-2.5 border-b border-white/10 px-4">
            <span class="inline-flex shrink-0 items-center">
                <x-application-logo class="h-8 w-auto" />
            </span>
            <div class="leading-tight">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-white/60">Warranties</div>
                <div class="text-sm font-bold text-white">Admin</div>
            </div>
        </div>
        <nav class="space-y-1 p-4 text-sm">
            @php
                $links = [
                    ['admin.dashboard', 'Dashboard'],
                    ['admin.warranties.index', 'Warranties'],
                    ['admin.warranties.pending', 'Pending Verification'],
                    ['admin.customers.index', 'Customers'],
                    ['admin.products.index', 'Products'],
                    ['admin.product-categories.index', 'Product Categories'],
                    ['admin.dealers.index', 'Dealers'],
                    ['admin.purchase-sources.index', 'Purchase Sources'],
                    ['admin.odoo.index', 'Odoo Sync'],
                    ['admin.notifications.index', 'Notifications'],
                    ['admin.reports.index', 'Reports'],
                    ['admin.users.index', 'Users'],
                    ['admin.roles.index', 'Roles and Permissions'],
                    ['admin.audit-logs.index', 'Audit Logs'],
                    ['admin.legal.edit', 'Legal Pages'],
                    ['admin.settings.edit', 'Settings'],
                ];
            @endphp
            @foreach ($links as [$route, $label])
                <a href="{{ route($route) }}"
                   class="block rounded-md px-3 py-2 {{ request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)) ? 'bg-brand text-white' : 'hover:bg-white/10' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="flex min-h-screen flex-1 flex-col">
        <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4">
            <button class="rounded-md border px-3 py-1 text-sm md:hidden" @click="sidebarOpen = !sidebarOpen">Menu</button>
            <div class="text-sm text-gray-500">
                @isset($breadcrumbs)
                    {{ $breadcrumbs }}
                @else
                    Administration
                @endisset
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-brand">Public site</a>
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-md bg-brand-navy px-3 py-1.5 text-white hover:bg-brand-ink">Logout</button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-6">
            @if (session('success'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-red-800">{{ session('error') }}</div>
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
    </div>
</div>
</body>
</html>
