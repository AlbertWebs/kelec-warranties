@extends('layouts.public')

@section('title', $title)

@section('content')
<article class="mx-auto max-w-3xl">
    <header class="mb-8 border-b border-gray-200 pb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">K-Elec Legal</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-ink sm:text-4xl">{{ $title }}</h1>
        <p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-600">{{ $intro }}</p>
        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
            @if ($updatedAt)
                <span>Last updated {{ \Illuminate\Support\Carbon::parse($updatedAt)->timezone(config('app.timezone'))->format('d M Y') }}</span>
            @endif
            @if ($canonicalUrl)
                <a href="{{ $canonicalUrl }}" class="text-brand hover:underline">Canonical page</a>
            @endif
        </div>
    </header>

    <div class="legal-content rounded-xl border border-gray-200 bg-white px-6 py-8 shadow-sm sm:px-10">
        {!! $contentHtml !!}
    </div>

    <aside class="mt-8 rounded-xl border border-brand/15 bg-brand-light/40 px-6 py-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-brand-navy">Need help?</h2>
        <p class="mt-2 text-sm text-gray-700">For privacy or warranty questions, contact K-Elec support.</p>
        <div class="mt-3 flex flex-wrap gap-4 text-sm font-medium">
            @if ($supportPhone)
                <a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}" class="text-brand hover:underline">{{ $supportPhone }}</a>
            @endif
            @if ($supportEmail)
                <a href="mailto:{{ $supportEmail }}" class="text-brand hover:underline">{{ $supportEmail }}</a>
            @endif
            <a href="{{ route('warranty-terms') }}" class="text-brand-navy hover:underline">Warranty Terms</a>
            <a href="{{ route('privacy-policy') }}" class="text-brand-navy hover:underline">Privacy Policy</a>
        </div>
    </aside>
</article>
@endsection
