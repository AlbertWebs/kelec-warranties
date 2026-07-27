@extends('layouts.admin')

@section('title', $warranty->reference)

@section('content')
<div class="mb-4 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">{{ $warranty->reference }}</h1>
        <p class="text-slate-500">{{ $warranty->status->label() }} · {{ $warranty->displayProductName() }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @can('approve', $warranty)
            <form method="POST" action="{{ route('admin.warranties.approve', $warranty) }}">@csrf
                <button class="rounded-lg bg-green-700 px-3 py-2 text-sm text-white">Approve</button>
            </form>
        @endcan
        @can('resendNotification', $warranty)
            <form method="POST" action="{{ route('admin.warranties.resend', $warranty) }}">@csrf
                <button class="rounded-lg border px-3 py-2 text-sm">Resend notification</button>
            </form>
        @endcan
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-xl border bg-white p-4 shadow-sm">
            <h2 class="font-semibold">Summary</h2>
            <dl class="mt-3 grid gap-3 text-sm md:grid-cols-2">
                <div><dt class="text-slate-500">Customer</dt><dd>{{ $warranty->customer->full_name }}</dd></div>
                <div><dt class="text-slate-500">Mobile</dt><dd>{{ $warranty->customer->mobile_normalized }}</dd></div>
                <div><dt class="text-slate-500">Serial</dt><dd>{{ $warranty->serial_number }}</dd></div>
                <div><dt class="text-slate-500">Purchase source</dt><dd>{{ $warranty->purchaseSource?->name }}</dd></div>
                <div><dt class="text-slate-500">Start</dt><dd>{{ optional($warranty->warranty_start_date)->format('d M Y') }}</dd></div>
                <div><dt class="text-slate-500">Expiry</dt><dd>{{ optional($warranty->warranty_expiry_date)->format('d M Y') }}</dd></div>
            </dl>
        </section>

        @can('update', $warranty)
            <section class="rounded-xl border bg-white p-4 shadow-sm">
                <h2 class="font-semibold">Update warranty</h2>
                <form method="POST" action="{{ route('admin.warranties.update', $warranty) }}" class="mt-4 grid gap-3 md:grid-cols-2">
                    @csrf @method('PUT')
                    <input name="serial_number" value="{{ old('serial_number', $warranty->serial_number) }}" class="rounded-lg border-slate-300" placeholder="Serial">
                    <input name="product_name" value="{{ old('product_name', $warranty->product_name) }}" class="rounded-lg border-slate-300" placeholder="Product name">
                    <input name="product_model" value="{{ old('product_model', $warranty->product_model) }}" class="rounded-lg border-slate-300" placeholder="Model">
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($warranty->purchase_date)->toDateString()) }}" class="rounded-lg border-slate-300">
                    <input name="invoice_number" value="{{ old('invoice_number', $warranty->invoice_number) }}" class="rounded-lg border-slate-300" placeholder="Invoice">
                    <input name="branch_name" value="{{ old('branch_name', $warranty->branch_name) }}" class="rounded-lg border-slate-300" placeholder="Branch">
                    <textarea name="internal_notes" class="rounded-lg border-slate-300 md:col-span-2" rows="3" placeholder="Internal notes">{{ old('internal_notes', $warranty->internal_notes) }}</textarea>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-white md:w-fit">Save changes</button>
                </form>
            </section>
        @endcan

        <section class="rounded-xl border bg-white p-4 shadow-sm">
            <h2 class="font-semibold">Status history</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($warranty->statusHistories as $history)
                    <li class="rounded-lg bg-slate-50 px-3 py-2">
                        {{ optional($history->from_status)->label() ?? '—' }} → {{ $history->to_status->label() }}
                        <span class="text-slate-500">· {{ $history->created_at->format('d M Y H:i') }} · {{ $history->changedBy?->name ?? 'System' }}</span>
                        @if ($history->reason)<div class="text-slate-600">{{ $history->reason }}</div>@endif
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="space-y-6">
        @can('reject', $warranty)
            <section class="rounded-xl border bg-white p-4 shadow-sm">
                <h2 class="font-semibold">Reject</h2>
                <form method="POST" action="{{ route('admin.warranties.reject', $warranty) }}" class="mt-3 space-y-3">
                    @csrf
                    <textarea name="rejection_reason" required class="w-full rounded-lg border-slate-300" rows="3" placeholder="Rejection reason (customer-facing)"></textarea>
                    <button class="rounded-lg bg-red-700 px-4 py-2 text-white">Reject warranty</button>
                </form>
            </section>
        @endcan

        <section class="rounded-xl border bg-white p-4 shadow-sm">
            <h2 class="font-semibold">Consent</h2>
            <ul class="mt-3 space-y-1 text-sm">
                <li>Privacy accepted: {{ $warranty->privacy_accepted ? 'Yes' : 'No' }}</li>
                <li>Marketing consent: {{ $warranty->marketing_consent ? 'Yes' : 'No' }}</li>
                <li>Consent at: {{ optional($warranty->consent_timestamp)->format('d M Y H:i') }}</li>
            </ul>
        </section>

        <section class="rounded-xl border bg-white p-4 shadow-sm">
            <h2 class="font-semibold">Documents</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($warranty->documents as $document)
                    <li><a class="text-red-700 hover:underline" href="{{ route('admin.documents.download', $document) }}">{{ $document->original_name }}</a></li>
                @empty
                    <li class="text-slate-500">No documents uploaded.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl border bg-white p-4 shadow-sm">
            <h2 class="font-semibold">Internal notes</h2>
            <form method="POST" action="{{ route('admin.warranties.notes', $warranty) }}" class="mt-3 space-y-3">
                @csrf
                <textarea name="body" required class="w-full rounded-lg border-slate-300" rows="3"></textarea>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_internal" value="1" checked> Internal only</label>
                <button class="rounded-lg border px-4 py-2">Add note</button>
            </form>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($warranty->notes as $note)
                    <li class="rounded-lg bg-slate-50 px-3 py-2">
                        <div class="text-slate-500">{{ $note->user?->name }} · {{ $note->created_at->format('d M Y H:i') }}</div>
                        <div>{{ $note->body }}</div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-xl border bg-white p-4 shadow-sm">
            <h2 class="font-semibold">Notifications</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($warranty->notificationLogs as $log)
                    <li>{{ $log->channel->value }} · {{ $log->status }} · {{ $log->notification_type }}</li>
                @empty
                    <li class="text-slate-500">No notifications yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
