@extends('layouts.public')

@section('title', 'Warranty Details')

@section('content')
<div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-bold">Warranty details</h1>
    <dl class="mt-6 grid gap-4 text-sm md:grid-cols-2">
        <div><dt class="text-slate-500">Reference</dt><dd class="font-semibold">{{ $warranty->reference }}</dd></div>
        <div><dt class="text-slate-500">Status</dt><dd>{{ $warranty->status->label() }}</dd></div>
        <div><dt class="text-slate-500">Customer</dt><dd>{{ $warranty->customer->maskedName() }}</dd></div>
        <div><dt class="text-slate-500">Mobile</dt><dd>{{ $warranty->customer->maskedMobile() }}</dd></div>
        <div><dt class="text-slate-500">Product</dt><dd>{{ $warranty->displayProductName() }}</dd></div>
        <div><dt class="text-slate-500">Model</dt><dd>{{ $warranty->displayModel() ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Serial</dt><dd>{{ $warranty->serial_number }}</dd></div>
        <div><dt class="text-slate-500">Place of purchase</dt><dd>{{ $warranty->purchaseSource?->name ?? $warranty->branch_name ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Start date</dt><dd>{{ optional($warranty->warranty_start_date)->format('d M Y') ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Expiry date</dt><dd>{{ optional($warranty->warranty_expiry_date)->format('d M Y') ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Remaining</dt><dd>{{ $warranty->remainingDays() !== null ? $warranty->remainingDays().' days' : '—' }}</dd></div>
        <div><dt class="text-slate-500">Registration status</dt><dd>{{ $warranty->status->label() }}</dd></div>
    </dl>

    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('warranty.certificate', $warranty->reference) }}" class="rounded-lg bg-red-700 px-4 py-2 text-white">Certificate</a>
        <form method="POST" action="{{ route('warranty.lookup.resend') }}">
            @csrf
            <input type="hidden" name="reference" value="{{ $warranty->reference }}">
            <input type="hidden" name="mobile_number" value="{{ $warranty->customer->mobile_number }}">
            <button class="rounded-lg border px-4 py-2">Resend details</button>
        </form>
    </div>
</div>
@endsection
