<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') - K-Elec Warranties</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 antialiased" x-data="{ sidebarOpen: false }">
<div class="min-h-screen md:flex">
    <aside class="fixed inset-y-0 left-0 z-40 w-72 transform bg-slate-950 text-slate-100 transition md:static md:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
        <div class="flex h-16 items-center border-b border-slate-800 px-5 text-lg font-semibold">
            <span class="text-red-400">K-Elec</span>&nbsp;Admin
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
                    ['admin.settings.edit', 'Settings'],
                ];
            @endphp
            @foreach ($links as [$route, $label])
                <a href="{{ route($route) }}"
                   class="block rounded-md px-3 py-2 {{ request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)) ? 'bg-red-700 text-white' : 'hover:bg-slate-800' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="flex min-h-screen flex-1 flex-col">
        <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4">
            <button class="rounded-md border px-3 py-1 text-sm md:hidden" @click="sidebarOpen = !sidebarOpen">Menu</button>
            <div class="text-sm text-slate-500">
                @isset($breadcrumbs)
                    {{ $breadcrumbs }}
                @else
                    Administration
                @endisset
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('home') }}" class="text-slate-500 hover:text-slate-800">Public site</a>
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-md bg-slate-900 px-3 py-1.5 text-white">Logout</button>
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
