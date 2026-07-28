<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') - K-Elec Warranties</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />
    <link rel="icon" type="image/png" sizes="32x32" href="https://k-elec.co.ke/favicon/favicon-32x32.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-hidden bg-brand-soft text-brand-ink antialiased" x-data="{ sidebarOpen: false }">
<div class="flex h-full">
    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        class="fixed inset-0 z-30 bg-black/40 md:hidden"
        @click="sidebarOpen = false"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-brand-ink text-slate-100 transition md:static md:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    >
        <div class="flex h-16 shrink-0 items-center border-b border-white/10 px-4">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center">
                <x-application-logo class="h-8 w-auto" />
            </a>
        </div>
        <nav class="scrollbar-hide flex-1 overflow-y-auto overscroll-contain px-3 py-4 text-sm">
            @php
                $groups = [
                    'Overview' => [
                        ['admin.dashboard', 'Dashboard', 'dashboard'],
                    ],
                    'Warranties' => [
                        ['admin.warranties.index', 'All warranties', 'warranties'],
                        ['admin.warranties.pending', 'Pending verification', 'pending'],
                        ['admin.claims.index', 'Claims', 'claims'],
                        ['admin.customers.index', 'Customers', 'customers'],
                    ],
                    'Catalog' => [
                        ['admin.products.index', 'Products', 'products'],
                        ['admin.product-categories.index', 'Categories', 'categories'],
                        ['admin.dealers.index', 'Dealers', 'dealers'],
                        ['admin.purchase-sources.index', 'Purchase sources', 'sources'],
                    ],
                    'Operations' => [
                        ['admin.odoo.index', 'Odoo sync', 'odoo'],
                        ['admin.odoo.products.index', 'Odoo product sync', 'odoo'],
                        ['admin.notifications.index', 'Notifications', 'notifications'],
                        ['admin.reports.index', 'Reports', 'reports'],
                    ],
                    'Administration' => [
                        ['admin.users.index', 'Users', 'users'],
                        ['admin.roles.index', 'Roles & permissions', 'roles'],
                        ['admin.audit-logs.index', 'Audit logs', 'audit'],
                        ['admin.legal.edit', 'Legal pages', 'legal'],
                        ['admin.settings.edit', 'Settings', 'settings'],
                    ],
                ];
            @endphp
            <div class="space-y-5">
                @foreach ($groups as $group => $links)
                    <div>
                        <p class="mb-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/35">{{ $group }}</p>
                        <div class="space-y-0.5">
                            @foreach ($links as [$route, $label, $icon])
                                @php
                                    $active = request()->routeIs($route)
                                        || request()->routeIs(str_replace('.index', '.*', $route));
                                @endphp
                                <a href="{{ route($route) }}"
                                   @class([
                                       'admin-nav-link group',
                                       'admin-nav-link-active' => $active,
                                   ])>
                                    <span @class([
                                        'admin-nav-icon',
                                        'admin-nav-icon-active' => $active,
                                    ])>
                                        <x-admin.nav-icon :name="$icon" />
                                    </span>
                                    <span class="truncate">{{ $label }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </nav>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4">
            <button type="button" class="rounded-md border border-gray-200 px-3 py-1.5 text-sm text-brand-ink md:hidden" @click="sidebarOpen = !sidebarOpen">Menu</button>
            <div class="min-w-0 flex-1 text-sm text-gray-500">
                @isset($breadcrumbs)
                    {{ $breadcrumbs }}
                @else
                    Administration
                @endisset
            </div>

            @php
                $adminUser = auth()->user();
                $adminRole = $adminUser->roles->first()?->name ?? 'staff';
                $adminRoleLabel = str_replace('_', ' ', $adminRole);
                $initials = collect(preg_split('/\s+/', trim($adminUser->name)))
                    ->filter()
                    ->take(2)
                    ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                    ->implode('');
            @endphp

            <div class="relative shrink-0" x-data="{ open: false }" @keydown.escape.window="open = false">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white py-1.5 pl-1.5 pr-2.5 text-left transition hover:border-brand/30 hover:bg-brand-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    aria-haspopup="menu"
                >
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-brand-navy text-xs font-semibold text-white">
                        {{ $initials ?: 'A' }}
                    </span>
                    <span class="hidden min-w-0 sm:block">
                        <span class="block truncate text-sm font-semibold leading-tight text-brand-ink">{{ $adminUser->name }}</span>
                        <span class="block truncate text-[11px] capitalize leading-tight text-gray-500">{{ $adminRoleLabel }}</span>
                    </span>
                    <svg class="h-4 w-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    @click.outside="open = false"
                    class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg shadow-gray-200/60"
                    role="menu"
                >
                    <div class="border-b border-gray-100 bg-brand-soft/60 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-brand-ink">{{ $adminUser->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $adminUser->email }}</p>
                    </div>

                    <div class="py-1.5">
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-brand-ink transition hover:bg-brand-soft hover:text-brand" role="menuitem" @click="open = false">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile settings
                        </a>
                        <a href="{{ route('profile.edit') }}#update-password" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-brand-ink transition hover:bg-brand-soft hover:text-brand" role="menuitem" @click="open = false">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            Change password
                        </a>
                        <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-brand-ink transition hover:bg-brand-soft hover:text-brand" role="menuitem" @click="open = false">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Public site
                        </a>
                        @if ($adminUser->hasRole('super_admin'))
                            <a href="{{ route('admin.demo-data.show') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 transition hover:bg-red-50" role="menuitem" @click="open = false">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                </svg>
                                Danger zone
                            </a>
                        @endif
                    </div>

                    <div class="border-t border-gray-100 py-1.5">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50" role="menuitem">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto overscroll-contain p-4 md:p-6">
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
