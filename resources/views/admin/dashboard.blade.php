@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    use App\Enums\WarrantyStatus;

    $monthMax = max(1, (int) collect($registrationsByMonth)->max());
    $sourceMax = max(1, (int) $bySource->max('total'));
    $statusTotal = max(1, (int) collect($byStatus)->sum());
    $needsAttention = $stats['pending'] + $stats['odoo_failures'] + $stats['sms_failures'] + $stats['email_failures'];
@endphp

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand">Mission control</p>
        <h1 class="mt-1 text-2xl font-bold text-brand-ink sm:text-3xl">
            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
            {{ strtok(auth()->user()->name, ' ') }}
        </h1>
        <p class="mt-1.5 text-sm text-slate-500">
            {{ now()->format('l, d M Y') }} · warranty operations overview
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.warranties.pending') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">
            Pending queue
            @if ($stats['pending'] > 0)
                <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-md bg-amber-500 px-1.5 py-0.5 text-xs font-bold text-white">{{ $stats['pending'] }}</span>
            @endif
        </a>
        <a href="{{ route('admin.warranties.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-slate-50">
            All warranties
        </a>
        @can('export', App\Models\Warranty::class)
            <a href="{{ route('admin.warranties.export') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-brand-navy px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-brand-ink">
                Export CSV
            </a>
        @endcan
    </div>
</div>

{{-- Primary KPIs --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('admin.warranties.index') }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand/30 hover:shadow">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total warranties</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-brand-ink">{{ number_format($stats['total']) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($stats['month']) }} this month · {{ number_format($stats['today']) }} today</p>
            </div>
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-navy/5 text-brand-navy transition group-hover:bg-brand-navy group-hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </span>
        </div>
    </a>

    <a href="{{ route('admin.warranties.index', ['status' => 'active']) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Active</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-700">{{ number_format($stats['active']) }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $stats['total'] > 0 ? round(($stats['active'] / $stats['total']) * 100) : 0 }}% of all registrations
                </p>
            </div>
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
        </div>
    </a>

    <a href="{{ route('admin.warranties.pending') }}" class="group rounded-xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm transition hover:border-amber-300 hover:shadow">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-800/70">Needs review</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-amber-900">{{ number_format($stats['pending']) }}</p>
                <p class="mt-1 text-xs text-amber-800/70">
                    {{ $stats['pending_verification'] }} pending · {{ $stats['under_review'] }} under review
                </p>
            </div>
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
        </div>
    </a>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Odoo success</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-brand-ink">{{ $stats['odoo_success_rate'] }}%</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($stats['odoo_failures']) }} pending failures</p>
            </div>
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </span>
        </div>
    </div>
</div>

{{-- Attention + secondary stats --}}
<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-1">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-brand-ink">Needs attention</h2>
            @if ($needsAttention > 0)
                <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">{{ $needsAttention }}</span>
            @endif
        </div>
        <ul class="mt-4 space-y-2">
            <li>
                <a href="{{ route('admin.warranties.pending') }}" class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2.5 text-sm transition hover:border-amber-200 hover:bg-amber-50/50">
                    <span class="font-medium text-brand-ink">Pending verification</span>
                    <span class="font-semibold text-amber-800">{{ $stats['pending'] }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.odoo.index') }}" class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2.5 text-sm transition hover:border-sky-200 hover:bg-sky-50/50">
                    <span class="font-medium text-brand-ink">Odoo failures</span>
                    <span class="font-semibold text-sky-800">{{ $stats['odoo_failures'] }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.notifications.index') }}" class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2.5 text-sm transition hover:border-brand/20 hover:bg-brand-soft/60">
                    <span class="font-medium text-brand-ink">SMS / email failures</span>
                    <span class="font-semibold text-brand">{{ $stats['sms_failures'] }}/{{ $stats['email_failures'] }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.warranties.index', ['status' => 'rejected']) }}" class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2.5 text-sm transition hover:border-red-200 hover:bg-red-50/40">
                    <span class="font-medium text-brand-ink">Rejected</span>
                    <span class="font-semibold text-red-700">{{ $stats['rejected'] }}</span>
                </a>
            </li>
        </ul>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
        <h2 class="text-base font-semibold text-brand-ink">Status mix</h2>
        <p class="mt-0.5 text-sm text-slate-500">Share of warranties by current status</p>
        <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-slate-100">
            @foreach ([
                WarrantyStatus::Active->value => 'bg-emerald-500',
                WarrantyStatus::PendingVerification->value => 'bg-amber-400',
                WarrantyStatus::UnderReview->value => 'bg-indigo-400',
                WarrantyStatus::Submitted->value => 'bg-sky-400',
                WarrantyStatus::Rejected->value => 'bg-red-400',
                WarrantyStatus::Expired->value => 'bg-orange-400',
                WarrantyStatus::Suspended->value => 'bg-violet-400',
                WarrantyStatus::Cancelled->value => 'bg-slate-400',
                WarrantyStatus::Draft->value => 'bg-slate-300',
            ] as $status => $color)
                @php $count = (int) ($byStatus[$status] ?? 0); @endphp
                @if ($count > 0)
                    <div class="{{ $color }} transition" style="width: {{ ($count / $statusTotal) * 100 }}%" title="{{ str_replace('_', ' ', $status) }}: {{ $count }}"></div>
                @endif
            @endforeach
        </div>
        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($byStatus->sortDesc() as $status => $total)
                @php
                    $enum = WarrantyStatus::tryFrom($status);
                @endphp
                <div class="flex items-center justify-between rounded-lg bg-slate-50/80 px-3 py-2 text-sm">
                    <span class="flex items-center gap-2">
                        @if ($enum)
                            <x-admin.status-badge :status="$enum" />
                        @else
                            <span class="capitalize text-slate-600">{{ str_replace('_', ' ', $status) }}</span>
                        @endif
                    </span>
                    <span class="font-semibold text-brand-ink">{{ number_format($total) }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500 sm:col-span-3">No warranty data yet.</p>
            @endforelse
        </div>
    </section>
</div>

{{-- Charts --}}
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-brand-ink">Registrations by month</h2>
        <p class="mt-0.5 text-sm text-slate-500">Last {{ $registrationsByMonth->count() }} months with activity</p>
        <ul class="mt-5 space-y-3">
            @forelse ($registrationsByMonth as $month => $total)
                <li>
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-600">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}</span>
                        <span class="font-semibold text-brand-ink">{{ number_format($total) }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-navy transition-all" style="width: {{ ($total / $monthMax) * 100 }}%"></div>
                    </div>
                </li>
            @empty
                <li class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No registrations yet.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-brand-ink">By purchase source</h2>
        <p class="mt-0.5 text-sm text-slate-500">Where customers registered purchases</p>
        <ul class="mt-5 space-y-3">
            @forelse ($bySource->sortByDesc('total') as $row)
                <li>
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-600">{{ $row->purchaseSource?->name ?? 'Unknown' }}</span>
                        <span class="font-semibold text-brand-ink">{{ number_format($row->total) }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand transition-all" style="width: {{ ($row->total / $sourceMax) * 100 }}%"></div>
                    </div>
                </li>
            @empty
                <li class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No data yet.</li>
            @endforelse
        </ul>
    </section>
</div>

{{-- Recent activity --}}
<div class="mt-6">
    <div class="mb-3 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-brand-ink">Recent activity</h2>
            <p class="mt-0.5 text-sm text-slate-500">Latest warranty registrations</p>
        </div>
        <a href="{{ route('admin.warranties.index') }}" class="text-sm font-semibold text-brand hover:underline">View all</a>
    </div>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recent as $warranty)
                        <tr class="group transition hover:bg-brand-soft/80">
                            <td class="px-4 py-3.5 align-top">
                                <a href="{{ route('admin.warranties.show', $warranty) }}" class="font-mono text-[13px] font-semibold text-brand hover:underline">{{ $warranty->reference }}</a>
                            </td>
                            <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $warranty->customer?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3.5 align-top">{{ $warranty->displayProductName() }}</td>
                            <td class="px-4 py-3.5 align-top"><x-admin.status-badge :status="$warranty->status" /></td>
                            <td class="px-4 py-3.5 align-top text-slate-600">{{ $warranty->created_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center text-sm text-slate-500">No recent warranties.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
