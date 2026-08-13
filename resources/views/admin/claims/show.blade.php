@extends('layouts.admin')

@section('title', $claim->reference)

@section('content')
@php
    $customer = $claim->customer;
    $warranty = $claim->warranty;
    $canManage = auth()->user()->can('claims.manage');
@endphp

<a href="{{ route('admin.claims.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-brand">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
    Back to claims
</a>

<div class="mt-3 mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between" x-data="{ copied: false }">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="font-mono text-2xl font-bold tracking-tight text-brand-ink">{{ $claim->reference }}</h1>
            <x-admin.status-badge :status="$claim->status" class="text-sm" />
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-slate-500 transition hover:bg-white hover:text-brand-ink"
                @click="navigator.clipboard.writeText(@js($claim->reference)); copied = true; setTimeout(() => copied = false, 1800)"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span x-text="copied ? 'Copied' : 'Copy'"></span>
            </button>
        </div>
        <p class="mt-1.5 text-sm text-slate-500">
            {{ $claim->subject }}
            <span class="text-slate-400">·</span>
            Submitted {{ $claim->created_at?->format('d M Y H:i') }}
            @if ($claim->updated_at && ! $claim->updated_at->eq($claim->created_at))
                <span class="text-slate-400">·</span>
                Updated {{ $claim->updated_at->format('d M Y H:i') }}
            @endif
        </p>
    </div>
</div>

<div class="grid gap-5 xl:grid-cols-3">
    <div class="space-y-5 xl:col-span-2">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-brand-ink">Claim details</h2>
            </div>
            <div class="space-y-4 px-5 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Subject</p>
                    <p class="mt-1 text-base font-semibold text-brand-ink">{{ $claim->subject }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Description</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $claim->description }}</p>
                </div>
                @if ($claim->customer_notes)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Customer notes</p>
                        <p class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $claim->customer_notes }}</p>
                    </div>
                @endif
                @if ($claim->photos->isNotEmpty())
                    <x-claim-photo-gallery :photos="$claim->photos" :claim="$claim" route-name="admin.claims.photos.show" />
                @else
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Photos</p>
                        <p class="mt-1 text-sm text-slate-500">No photos were uploaded with this claim.</p>
                    </div>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-brand-ink">Linked warranty</h2>
            </div>
            <dl class="grid gap-4 px-5 py-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Warranty</dt>
                    <dd class="mt-1">
                        @if ($warranty)
                            <a href="{{ route('admin.warranties.show', $warranty) }}" class="font-mono text-sm font-semibold text-brand hover:underline">{{ $warranty->reference }}</a>
                        @else
                            <span class="text-sm text-slate-500">—</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Product</dt>
                    <dd class="mt-1 text-sm font-medium text-brand-ink">{{ $warranty?->displayProductName() ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Model</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $warranty?->displayModel() ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Serial</dt>
                    <dd class="mt-1 font-mono text-sm text-slate-700">{{ $warranty?->serial_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Warranty status</dt>
                    <dd class="mt-1">
                        @if ($warranty?->status)
                            <x-admin.status-badge :status="$warranty->status" />
                        @else
                            <span class="text-sm text-slate-500">—</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </section>
    </div>

    <div class="space-y-5">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-brand-ink">Customer</h2>
            </div>
            <div class="space-y-3 px-5 py-5 text-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Name</p>
                    <p class="mt-1 font-medium text-brand-ink">
                        @if ($customer)
                            <a href="{{ route('admin.customers.show', $customer) }}" class="text-brand hover:underline">{{ $customer->full_name }}</a>
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Mobile</p>
                    <p class="mt-1 text-slate-700">
                        @if ($customer?->mobile_number)
                            <a href="tel:{{ preg_replace('/\s+/', '', $customer->mobile_number) }}" class="hover:text-brand">{{ $customer->mobile_number }}</a>
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</p>
                    <p class="mt-1 text-slate-700">
                        @if ($customer?->email)
                            <a href="mailto:{{ $customer->email }}" class="hover:text-brand">{{ $customer->email }}</a>
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-brand-ink">Update claim</h2>
                <p class="mt-1 text-xs text-slate-500">Changing status can notify the customer by email/SMS.</p>
            </div>

            @if ($canManage)
                <form method="POST" action="{{ route('admin.claims.update', $claim) }}" class="space-y-4 px-5 py-5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="status" class="block text-sm font-medium text-brand-ink">Status</label>
                        <select id="status" name="status" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $claim->status->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="admin_notes" class="block text-sm font-medium text-brand-ink">Admin notes</label>
                        <textarea id="admin_notes" name="admin_notes" rows="5" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand" placeholder="Internal notes shared with the customer when relevant…">{{ old('admin_notes', $claim->admin_notes) }}</textarea>
                        @error('admin_notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                        <input type="hidden" name="notify_customer" value="0">
                        <input type="checkbox" name="notify_customer" value="1" class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand" @checked(old('notify_customer', '1') === '1')>
                        <span>
                            <span class="font-semibold text-brand-ink">Notify customer</span>
                            <span class="mt-0.5 block text-xs text-slate-500">Sends an update when the status changes.</span>
                        </span>
                    </label>
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">
                        Save changes
                    </button>
                </form>
            @else
                <div class="px-5 py-5 text-sm text-slate-600">
                    <p class="font-medium text-brand-ink">{{ $claim->status->label() }}</p>
                    @if ($claim->admin_notes)
                        <p class="mt-3 whitespace-pre-wrap">{{ $claim->admin_notes }}</p>
                    @else
                        <p class="mt-3 text-slate-500">No admin notes yet.</p>
                    @endif
                    <p class="mt-4 text-xs text-slate-400">You have view-only access to claims.</p>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
