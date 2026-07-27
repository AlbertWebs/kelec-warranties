@extends('layouts.admin')

@section('title', $customer->full_name)

@section('content')
<a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-brand">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
    Back to customers
</a>

<div class="mt-3 mb-5">
    <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-2xl font-bold text-brand-ink">{{ $customer->full_name }}</h1>
        @if ($customer->password)
            <span class="inline-flex rounded-md bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-600/20">Portal account</span>
        @endif
        @if ($customer->possible_duplicate)
            <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20">Possible duplicate</span>
        @endif
    </div>
    <p class="mt-1 text-sm text-slate-500">
        {{ $customer->mobile_number ?? $customer->mobile_normalized }}
        @if ($customer->email)
            <span class="text-slate-400">·</span> {{ $customer->email }}
        @endif
    </p>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-brand-ink">Customer details</h2>
        <p class="mt-1 text-sm text-slate-500">Update contact and location information.</p>
        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <div class="auth-field sm:col-span-2">
                <label class="auth-label" for="full_name">Full name</label>
                <input id="full_name" name="full_name" value="{{ old('full_name', $customer->full_name) }}" class="auth-input" required>
            </div>
            <div class="auth-field">
                <label class="auth-label" for="mobile_number">Mobile</label>
                <input id="mobile_number" name="mobile_number" value="{{ old('mobile_number', $customer->mobile_number) }}" class="auth-input" required>
            </div>
            <div class="auth-field">
                <label class="auth-label" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" class="auth-input">
            </div>
            <div class="auth-field">
                <label class="auth-label" for="county">County</label>
                <input id="county" name="county" value="{{ old('county', $customer->county) }}" class="auth-input" placeholder="County">
            </div>
            <div class="auth-field">
                <label class="auth-label" for="town">Town</label>
                <input id="town" name="town" value="{{ old('town', $customer->town) }}" class="auth-input" placeholder="Town">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600 sm:col-span-2">
                <input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent', $customer->marketing_consent)) class="rounded border-slate-300 text-brand focus:ring-brand">
                Marketing consent
            </label>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">
                    Save changes
                </button>
            </div>
        </form>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-brand-ink">Warranties</h2>
            <span class="text-xs text-slate-400">{{ $customer->warranties->count() }}</span>
        </div>
        <div class="mt-3 overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2.5">Reference</th>
                        <th class="px-3 py-2.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($customer->warranties as $warranty)
                        <tr class="transition hover:bg-brand-soft/80">
                            <td class="px-3 py-3">
                                <a href="{{ route('admin.warranties.show', $warranty) }}" class="font-mono text-[13px] font-semibold text-brand hover:underline">{{ $warranty->reference }}</a>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $warranty->displayProductName() }}</p>
                            </td>
                            <td class="px-3 py-3"><x-admin.status-badge :status="$warranty->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-3 py-8 text-center text-sm text-slate-500">No warranties for this customer.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
