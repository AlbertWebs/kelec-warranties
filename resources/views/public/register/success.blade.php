@extends('layouts.public')

@section('title', 'Registration Successful')

@section('content')
<div class="mx-auto max-w-2xl rounded-2xl border border-green-200 bg-white p-8 shadow-sm">
    <h1 class="text-3xl font-bold text-green-800">Registration received</h1>
    <p class="mt-2 text-slate-600">Thank you. Your warranty registration has been saved.</p>

    <dl class="mt-6 grid gap-4 text-sm md:grid-cols-2">
        <div><dt class="text-slate-500">Reference</dt><dd class="text-lg font-semibold">{{ $warranty->reference }}</dd></div>
        <div><dt class="text-slate-500">Status</dt><dd class="font-semibold">{{ $warranty->status->label() }}</dd></div>
        <div><dt class="text-slate-500">Product</dt><dd>{{ $warranty->displayProductName() }}</dd></div>
        <div><dt class="text-slate-500">Serial</dt><dd>{{ $warranty->serial_number }}</dd></div>
        <div><dt class="text-slate-500">Expected duration</dt><dd>{{ $warranty->warranty_duration_months }} months</dd></div>
        <div><dt class="text-slate-500">Expiry</dt><dd>{{ optional($warranty->warranty_expiry_date)->format('d M Y') ?? 'Pending verification' }}</dd></div>
    </dl>

    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('warranty.certificate', $warranty->reference) }}" class="rounded-lg bg-red-700 px-4 py-2 text-white">View certificate</a>
        <a href="{{ route('warranty.certificate.download', $warranty->reference) }}" class="rounded-lg border px-4 py-2">Download PDF</a>
        <a href="{{ route('warranty.lookup') }}" class="rounded-lg border px-4 py-2">Lookup warranty</a>
    </div>
</div>
@endsection
