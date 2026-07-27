@extends('layouts.public')

@section('title', 'Warranty Lookup')

@section('content')
<div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-3xl font-bold">Lookup active warranty</h1>
    <p class="mt-2 text-slate-600">For privacy, a mobile number is required together with your warranty reference or serial number.</p>

    <form method="POST" action="{{ route('warranty.lookup.store') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium">Warranty reference</label>
            <input name="reference" value="{{ $reference }}" class="w-full rounded-lg border-slate-300" placeholder="KEL-WTY-2026-000001">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Serial number</label>
            <input name="serial_number" value="{{ $serial_number ?? request('serial') }}" class="w-full rounded-lg border-slate-300">
            <p class="mt-1 text-xs text-slate-500">QR certificate links open this page with the reference or serial prefilled. Mobile verification is still required.</p>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Registered mobile number</label>
            <input name="mobile_number" value="{{ old('mobile_number') }}" required class="w-full rounded-lg border-slate-300" placeholder="07XXXXXXXX">
        </div>
        <button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Search</button>
    </form>
</div>
@endsection
