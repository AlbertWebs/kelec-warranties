@extends('layouts.public')

@section('title', 'Find Nearest Store')

@section('content')
<div class="mx-auto max-w-2xl py-10 text-center">
    <p class="text-xs font-semibold uppercase tracking-wider text-brand">Stores</p>
    <h1 class="mt-2 text-3xl font-bold text-brand-ink sm:text-4xl">Find nearest store</h1>
    <p class="mt-4 text-base leading-relaxed text-gray-600">
        A store locator is coming soon. In the meantime, visit our main website or call support for directions to a K-Elec brand shop.
    </p>

    <div class="mt-8 inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800">
        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
        Coming soon
    </div>

    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
        <a href="https://k-elec.co.ke/" target="_blank" rel="noopener" class="btn-brand">Visit main website</a>
        <a href="{{ support_phone_tel() }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 font-semibold text-brand-ink hover:border-brand hover:text-brand">Call {{ support_phone() }}</a>
    </div>

    <p class="mt-8 text-sm text-gray-500">Current brand shops: Sarin · CBD · Westlands</p>
</div>
@endsection
