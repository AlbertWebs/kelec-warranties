@extends('layouts.public')

@section('title', 'K-Elec Warranty Portal')

@section('content')
<div class="grid gap-8 md:grid-cols-2 md:items-center">
    <div>
        <p class="text-sm font-semibold uppercase tracking-wide text-red-700">K-Elec Warranty Management</p>
        <h1 class="mt-2 text-4xl font-bold tracking-tight text-slate-900">Register and track your appliance warranty with confidence</h1>
        <p class="mt-4 text-lg text-slate-600">Enter your product serial number to register a new warranty or look up an existing active warranty securely.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('register-warranty.create') }}" class="rounded-lg bg-red-700 px-5 py-3 font-semibold text-white hover:bg-red-800">Register Warranty</a>
            <a href="{{ route('warranty.lookup') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">Lookup Warranty</a>
        </div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">What you will need</h2>
        <ul class="mt-4 space-y-3 text-slate-600">
            <li>Product serial number</li>
            <li>Purchase date and place of purchase</li>
            <li>Mobile number for confirmation</li>
            <li>Optional receipt or invoice number</li>
        </ul>
        <p class="mt-6 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">Marketing consent is optional and never required to activate your warranty.</p>
    </div>
</div>
@endsection
