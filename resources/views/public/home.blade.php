@extends('layouts.public')

@section('title', 'K-Elec Warranty Portal')

@section('content')
<div class="grid gap-8 md:grid-cols-2 md:items-center">
    <div>
        <p class="text-sm font-semibold uppercase tracking-wide text-brand">K-Elec · Korean Tech, Kenyan Trust</p>
        <h1 class="mt-2 text-4xl font-bold tracking-tight text-brand-ink">Register and track your appliance warranty with confidence</h1>
        <p class="mt-4 text-lg text-gray-600">Enter your product serial number to register a new warranty or look up an existing active warranty securely.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('register-warranty.create') }}" class="btn-brand px-5 py-3">Register Warranty</a>
            <a href="{{ route('warranty.lookup') }}" class="rounded-lg border border-gray-300 bg-white px-5 py-3 font-semibold text-brand-navy hover:border-brand hover:text-brand">Lookup Warranty</a>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 inline-flex rounded-md bg-brand px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">36 months warranty confidence</div>
        <h2 class="text-lg font-semibold text-brand-navy">What you will need</h2>
        <ul class="mt-4 space-y-3 text-gray-600">
            <li>Product serial number</li>
            <li>Purchase date and place of purchase</li>
            <li>Mobile number for confirmation</li>
            <li>Optional receipt or invoice number</li>
        </ul>
        <p class="mt-6 rounded-lg bg-brand-soft p-4 text-sm text-gray-600">Marketing consent is optional and never required to activate your warranty.</p>
    </div>
</div>
@endsection
