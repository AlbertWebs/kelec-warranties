@extends('layouts.admin')

@section('title', 'Profile')

@section('content')
@php
    $roleLabel = str_replace('_', ' ', $user->roles->first()?->name ?? 'staff');
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">Account</p>
        <h1 class="mt-1 text-2xl font-bold text-brand-ink">Profile settings</h1>
        <p class="mt-1 text-sm text-gray-500">Manage your name, email, and password for the admin portal.</p>
    </div>
    <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-brand-navy text-sm font-semibold text-white">
            {{ $initials ?: 'A' }}
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-brand-ink">{{ $user->name }}</p>
            <p class="truncate text-xs text-gray-500">{{ $user->email }}</p>
            <p class="truncate text-[11px] capitalize text-brand-navy/70">{{ $roleLabel }}</p>
        </div>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            @include('profile.partials.update-profile-information-form')
        </section>

        <section id="update-password" class="scroll-mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            @include('profile.partials.update-password-form')
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm sm:p-6">
            <h2 class="text-sm font-semibold text-amber-950">Security tips</h2>
            <ul class="mt-3 space-y-2 text-sm text-amber-900/80">
                <li>Use a unique password for this admin account.</li>
                <li>Keep your email up to date for recovery and notifications.</li>
                <li>Log out on shared devices after finishing work.</li>
            </ul>
        </section>

        <section class="rounded-xl border border-red-200 bg-white p-5 shadow-sm sm:p-6">
            @include('profile.partials.delete-user-form')
        </section>
    </div>
</div>
@endsection
