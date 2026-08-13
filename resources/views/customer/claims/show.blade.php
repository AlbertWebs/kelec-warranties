@extends('layouts.public')

@section('title', $claim->reference)

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('customer.claims.index') }}" class="text-sm font-medium text-brand hover:underline">← Back to claims</a>
    <div class="mt-2 flex flex-wrap items-center gap-2.5">
        <h1 class="text-2xl font-bold text-brand-ink sm:text-3xl">{{ $claim->reference }}</h1>
        <x-admin.status-badge :status="$claim->status" />
    </div>
    <p class="mt-1 text-sm text-gray-600">Submitted {{ $claim->created_at->format('d M Y H:i') }}</p>

    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Warranty</dt>
                <dd class="font-medium">
                    <a href="{{ route('customer.warranties.show', $claim->warranty) }}" class="text-brand hover:underline">{{ $claim->warranty->reference }}</a>
                </dd>
            </div>
            <div class="flex justify-between gap-4"><dt class="text-gray-500">Product</dt><dd class="font-medium">{{ $claim->warranty->displayProductName() }}</dd></div>
            <div><dt class="text-gray-500">Subject</dt><dd class="mt-1 font-medium text-brand-ink">{{ $claim->subject }}</dd></div>
            <div><dt class="text-gray-500">Description</dt><dd class="mt-1 whitespace-pre-wrap text-brand-ink">{{ $claim->description }}</dd></div>
            @if ($claim->customer_notes)
                <div><dt class="text-gray-500">Your notes</dt><dd class="mt-1 whitespace-pre-wrap">{{ $claim->customer_notes }}</dd></div>
            @endif
        </dl>
    </section>
</div>
@endsection
