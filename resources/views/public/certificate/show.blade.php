@extends('layouts.public')

@section('title', 'Warranty Certificate')

@section('content')
<div class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-red-700">K-Elec</p>
            <h1 class="mt-1 text-3xl font-bold">Warranty Certificate</h1>
        </div>
        <a href="{{ route('warranty.certificate.download', $warranty->reference) }}" class="rounded-lg bg-red-700 px-4 py-2 text-white">Download PDF</a>
    </div>

    <dl class="mt-8 grid gap-4 text-sm md:grid-cols-2">
        <div><dt class="text-slate-500">Reference</dt><dd class="font-semibold">{{ $warranty->reference }}</dd></div>
        <div><dt class="text-slate-500">Status</dt><dd>{{ $warranty->status->label() }}</dd></div>
        <div><dt class="text-slate-500">Customer</dt><dd>{{ $warranty->customer->full_name }}</dd></div>
        <div><dt class="text-slate-500">Product</dt><dd>{{ $warranty->displayProductName() }}</dd></div>
        <div><dt class="text-slate-500">Model</dt><dd>{{ $warranty->displayModel() ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Serial</dt><dd>{{ $warranty->serial_number }}</dd></div>
        <div><dt class="text-slate-500">Purchase source</dt><dd>{{ $warranty->purchaseSource?->name ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Purchase date</dt><dd>{{ optional($warranty->purchase_date)->format('d M Y') ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Start</dt><dd>{{ optional($warranty->warranty_start_date)->format('d M Y') ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Expiry</dt><dd>{{ optional($warranty->warranty_expiry_date)->format('d M Y') ?? '—' }}</dd></div>
    </dl>

    <p class="mt-8 text-sm text-slate-600">Secure lookup (mobile verification required):</p>
    <p class="mt-2 break-all text-sm"><a class="text-red-700 underline" href="{{ route('warranty.lookup', ['reference' => $warranty->reference]) }}">{{ route('warranty.lookup', ['reference' => $warranty->reference]) }}</a></p>
    <img class="mt-4 h-32 w-32" alt="Warranty lookup QR" src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode(route('warranty.lookup', ['reference' => $warranty->reference])) }}">
    <p class="mt-2 text-sm text-slate-600">Support: {{ config('app.name') }} · <a href="{{ route('warranty-terms') }}" class="text-red-700 underline">Warranty Terms</a></p>
</div>
@endsection
