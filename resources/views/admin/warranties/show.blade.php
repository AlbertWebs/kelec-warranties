@extends('layouts.admin')

@section('title', $warranty->reference)

@section('content')
@php
    use App\Enums\WarrantyStatus;

    $remainingDays = $warranty->remainingDays();
    $expiry = $warranty->warranty_expiry_date;
    $canApprove = auth()->user()->can('approve', $warranty)
        && in_array($warranty->status, [
            WarrantyStatus::Submitted,
            WarrantyStatus::PendingVerification,
            WarrantyStatus::UnderReview,
        ], true);
    $canReject = auth()->user()->can('reject', $warranty)
        && in_array($warranty->status, [
            WarrantyStatus::Submitted,
            WarrantyStatus::PendingVerification,
            WarrantyStatus::UnderReview,
        ], true);
@endphp

<a href="{{ route('admin.warranties.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-brand">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
    Back to warranties
</a>

<div class="mt-3 mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between" x-data="{ copied: false }">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="font-mono text-2xl font-bold tracking-tight text-brand-ink">{{ $warranty->reference }}</h1>
            <x-admin.status-badge :status="$warranty->status" class="text-sm" />
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-slate-500 transition hover:bg-white hover:text-brand-ink"
                @click="navigator.clipboard.writeText(@js($warranty->reference)); copied = true; setTimeout(() => copied = false, 1800)"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span x-text="copied ? 'Copied' : 'Copy'"></span>
            </button>
        </div>
        <p class="mt-1.5 text-sm text-slate-500">
            {{ $warranty->displayProductName() }}
            @if ($warranty->displayModel())
                <span class="text-slate-400">·</span> {{ $warranty->displayModel() }}
            @endif
            <span class="text-slate-400">·</span>
            Registered {{ optional($warranty->registration_date ?? $warranty->created_at)->format('d M Y') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if ($canApprove)
            <form method="POST" action="{{ route('admin.warranties.approve', $warranty) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Approve
                </button>
            </form>
        @endif
        @can('resendNotification', $warranty)
            <form method="POST" action="{{ route('admin.warranties.resend', $warranty) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-slate-50">
                    Resend notification
                </button>
            </form>
        @endcan
        @if ($warranty->status === WarrantyStatus::Active)
            <a href="{{ route('warranty.certificate', $warranty->reference) }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-slate-50">
                Certificate
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        @endif
    </div>
</div>

@if ($warranty->status === WarrantyStatus::Rejected && $warranty->rejection_reason)
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold">Rejected</p>
        <p class="mt-0.5">{{ $warranty->rejection_reason }}</p>
    </div>
@endif

@if ($warranty->requires_manual_verification && in_array($warranty->status, [WarrantyStatus::PendingVerification, WarrantyStatus::UnderReview], true))
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold">Needs manual verification</p>
        <p class="mt-0.5">{{ $warranty->odoo_validation_message ?: 'This registration is waiting for staff review before it can be activated.' }}</p>
    </div>
@endif

<div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Warranty start</p>
        <p class="mt-1 text-lg font-semibold text-brand-ink">{{ optional($warranty->warranty_start_date)->format('d M Y') ?? '—' }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Expiry</p>
        <p @class([
            'mt-1 text-lg font-semibold',
            'text-red-600' => $expiry && $expiry->isPast(),
            'text-amber-700' => $expiry && ! $expiry->isPast() && $remainingDays !== null && $remainingDays <= 30,
            'text-brand-ink' => ! ($expiry && ($expiry->isPast() || ($remainingDays !== null && $remainingDays <= 30))),
        ])>{{ optional($expiry)->format('d M Y') ?? '—' }}</p>
        @if ($remainingDays !== null && $remainingDays >= 0)
            <p class="mt-0.5 text-xs text-slate-500">{{ $remainingDays }} days remaining</p>
        @elseif ($expiry && $expiry->isPast())
            <p class="mt-0.5 text-xs text-red-500">Expired</p>
        @endif
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Purchase source</p>
        <p class="mt-1 text-lg font-semibold text-brand-ink">{{ $warranty->purchaseSource?->name ?? '—' }}</p>
        <p class="mt-0.5 text-xs text-slate-500">{{ $warranty->registration_source?->label() ?? '—' }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Odoo validation</p>
        <p class="mt-1">
            @if ($warranty->odoo_validated)
                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-sm font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Validated</span>
            @else
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-sm font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">Not validated</span>
            @endif
        </p>
        @if ($warranty->approver)
            <p class="mt-1 text-xs text-slate-500">Approved by {{ $warranty->approver->name }}</p>
        @endif
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-brand-ink">Overview</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50/80 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Customer</dt>
                    <dd class="mt-1">
                        <a href="{{ route('admin.customers.show', $warranty->customer) }}" class="font-semibold text-brand hover:underline">
                            {{ $warranty->customer->full_name }}
                        </a>
                        <p class="mt-0.5 tabular-nums text-sm text-slate-500">{{ $warranty->customer->mobile_number ?? $warranty->customer->mobile_normalized }}</p>
                        @if ($warranty->customer->email)
                            <p class="truncate text-sm text-slate-500">{{ $warranty->customer->email }}</p>
                        @endif
                    </dd>
                </div>
                <div class="rounded-lg bg-slate-50/80 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Serial number</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold text-brand-ink">{{ $warranty->serial_number }}</dd>
                    @if ($warranty->invoice_number)
                        <p class="mt-1 text-sm text-slate-500">Invoice {{ $warranty->invoice_number }}</p>
                    @endif
                </div>
                <div class="rounded-lg bg-slate-50/80 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Product</dt>
                    <dd class="mt-1 font-semibold text-brand-ink">{{ $warranty->displayProductName() }}</dd>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $warranty->displayModel() ?: 'No model' }}
                        @if ($warranty->product?->category)
                            · {{ $warranty->product->category->name }}
                        @endif
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50/80 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Purchase</dt>
                    <dd class="mt-1 font-semibold text-brand-ink">{{ optional($warranty->purchase_date)->format('d M Y') ?? '—' }}</dd>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $warranty->dealer?->name ?? $warranty->branch_name ?? 'No branch / dealer' }}
                    </p>
                </div>
            </dl>

            @if ($warranty->customer_notes)
                <div class="mt-4 rounded-lg border border-slate-100 bg-white p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Customer notes</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $warranty->customer_notes }}</p>
                </div>
            @endif
        </section>

        @can('update', $warranty)
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-brand-ink">Edit details</h2>
                <p class="mt-1 text-sm text-slate-500">Correct registration fields without changing status.</p>
                <form method="POST" action="{{ route('admin.warranties.update', $warranty) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div class="auth-field">
                        <label class="auth-label" for="serial_number">Serial number</label>
                        <input id="serial_number" name="serial_number" value="{{ old('serial_number', $warranty->serial_number) }}" class="auth-input" required>
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="invoice_number">Invoice number</label>
                        <input id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $warranty->invoice_number) }}" class="auth-input">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="product_name">Product name</label>
                        <input id="product_name" name="product_name" value="{{ old('product_name', $warranty->product_name) }}" class="auth-input">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="product_model">Model</label>
                        <input id="product_model" name="product_model" value="{{ old('product_model', $warranty->product_model) }}" class="auth-input">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="purchase_date">Purchase date</label>
                        <input id="purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date', optional($warranty->purchase_date)->toDateString()) }}" class="auth-input">
                    </div>
                    <div class="auth-field">
                        <label class="auth-label" for="branch_name">Branch</label>
                        <input id="branch_name" name="branch_name" value="{{ old('branch_name', $warranty->branch_name) }}" class="auth-input">
                    </div>
                    <div class="auth-field sm:col-span-2">
                        <label class="auth-label" for="internal_notes">Internal notes</label>
                        <textarea id="internal_notes" name="internal_notes" rows="3" class="auth-input" placeholder="Visible only to staff">{{ old('internal_notes', $warranty->internal_notes) }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">
                            Save changes
                        </button>
                    </div>
                </form>
            </section>
        @endcan

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-brand-ink">Status history</h2>
                <span class="text-xs text-slate-400">{{ $warranty->statusHistories->count() }} events</span>
            </div>
            @if ($warranty->statusHistories->isEmpty())
                <p class="mt-4 text-sm text-slate-500">No status changes recorded yet.</p>
            @else
                <ol class="relative mt-5 space-y-0 border-l border-slate-200 ml-2">
                    @foreach ($warranty->statusHistories as $history)
                        <li class="relative pb-5 pl-5 last:pb-0">
                            <span class="absolute -left-1.5 top-1.5 h-3 w-3 rounded-full border-2 border-white bg-brand shadow-sm"></span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-slate-500">{{ optional($history->from_status)->label() ?? '—' }}</span>
                                <svg class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                                <x-admin.status-badge :status="$history->to_status" />
                            </div>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ $history->created_at->format('d M Y · H:i') }}
                                · {{ $history->changedBy?->name ?? 'System' }}
                            </p>
                            @if ($history->reason)
                                <p class="mt-1.5 text-sm text-slate-600">{{ $history->reason }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        @if ($warranty->claims->isNotEmpty())
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-brand-ink">Claims</h2>
                <ul class="mt-3 divide-y divide-slate-100">
                    @foreach ($warranty->claims as $claim)
                        <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <a href="{{ route('admin.claims.show', $claim) }}" class="font-mono text-sm font-semibold text-brand hover:underline">{{ $claim->reference }}</a>
                                <p class="truncate text-sm text-slate-500">{{ $claim->subject }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-medium text-slate-500">{{ $claim->status->label() }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <div class="space-y-6">
        @if ($canReject)
            <section class="rounded-xl border border-red-200 bg-red-50/40 p-5 shadow-sm">
                <h2 class="text-base font-semibold text-red-700">Reject warranty</h2>
                <p class="mt-1 text-sm text-red-900/70">Reason is shown to the customer.</p>
                <form method="POST" action="{{ route('admin.warranties.reject', $warranty) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="rejection_reason" required rows="3" class="auth-input" placeholder="Explain why this registration cannot be approved"></textarea>
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                        Reject warranty
                    </button>
                </form>
            </section>
        @endif

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-brand-ink">Consent</h2>
            <ul class="mt-3 space-y-2.5 text-sm">
                <li class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Privacy</span>
                    <span @class(['font-semibold', 'text-emerald-700' => $warranty->privacy_accepted, 'text-slate-500' => ! $warranty->privacy_accepted])>
                        {{ $warranty->privacy_accepted ? 'Accepted' : 'No' }}
                    </span>
                </li>
                <li class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Marketing</span>
                    <span @class(['font-semibold', 'text-emerald-700' => $warranty->marketing_consent, 'text-slate-500' => ! $warranty->marketing_consent])>
                        {{ $warranty->marketing_consent ? 'Yes' : 'No' }}
                    </span>
                </li>
                <li class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">Timestamp</span>
                    <span class="font-medium text-brand-ink">{{ optional($warranty->consent_timestamp)->format('d M Y H:i') ?? '—' }}</span>
                </li>
                @if ($warranty->consent_source)
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Source</span>
                        <span class="font-medium text-brand-ink">{{ str_replace('_', ' ', $warranty->consent_source) }}</span>
                    </li>
                @endif
            </ul>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-brand-ink">Documents</h2>
                <span class="text-xs text-slate-400">{{ $warranty->documents->count() }}</span>
            </div>
            <ul class="mt-3 space-y-2">
                @forelse ($warranty->documents as $document)
                    <li>
                        <a href="{{ route('admin.documents.download', $document) }}"
                           class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2.5 text-sm transition hover:border-brand/20 hover:bg-brand-soft/60">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-brand-ink">{{ $document->original_name }}</span>
                                <span class="text-xs text-slate-400">{{ $document->type ?? 'file' }}</span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="rounded-lg border border-dashed border-slate-200 px-3 py-6 text-center text-sm text-slate-500">No documents uploaded.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-brand-ink">Staff notes</h2>
            <form method="POST" action="{{ route('admin.warranties.notes', $warranty) }}" class="mt-3 space-y-3">
                @csrf
                <textarea name="body" required rows="3" class="auth-input" placeholder="Add an internal note…"></textarea>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="is_internal" value="1" checked class="rounded border-slate-300 text-brand focus:ring-brand">
                    Internal only
                </label>
                <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-slate-50">
                    Add note
                </button>
            </form>
            <ul class="mt-4 space-y-2">
                @forelse ($warranty->notes as $note)
                    <li class="rounded-lg bg-slate-50 px-3 py-2.5 text-sm">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
                            <span class="font-medium text-slate-600">{{ $note->user?->name ?? 'Staff' }}</span>
                            <span>·</span>
                            <span>{{ $note->created_at->format('d M Y H:i') }}</span>
                            @if ($note->is_internal)
                                <span class="rounded bg-slate-200/80 px-1.5 py-0.5 font-medium text-slate-600">Internal</span>
                            @endif
                        </div>
                        <p class="mt-1 whitespace-pre-wrap text-brand-ink">{{ $note->body }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No notes yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-brand-ink">Notifications</h2>
                <span class="text-xs text-slate-400">{{ $warranty->notificationLogs->count() }}</span>
            </div>
            <ul class="mt-3 space-y-2">
                @forelse ($warranty->notificationLogs as $log)
                    <li class="rounded-lg border border-slate-100 px-3 py-2.5 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium text-brand-ink">{{ $log->channel->label() }}</span>
                            <span @class([
                                'rounded-md px-1.5 py-0.5 text-xs font-semibold',
                                'bg-emerald-50 text-emerald-700' => $log->status === 'sent',
                                'bg-red-50 text-red-700' => $log->status === 'failed',
                                'bg-slate-100 text-slate-600' => ! in_array($log->status, ['sent', 'failed'], true),
                            ])>{{ ucfirst($log->status) }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">{{ str_replace('_', ' ', $log->notification_type) }}</p>
                        @if ($log->sent_at || $log->failed_at)
                            <p class="mt-0.5 text-xs text-slate-400">{{ optional($log->sent_at ?? $log->failed_at)->format('d M Y H:i') }}</p>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No notifications yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
