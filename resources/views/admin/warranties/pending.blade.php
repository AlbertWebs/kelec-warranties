@extends('layouts.admin')

@section('title', 'Pending Verification')

@section('content')
<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-brand-ink">Pending Verification</h1>
        <p class="mt-1 text-sm text-slate-500">Registrations awaiting manual review</p>
    </div>
    <a href="{{ route('admin.warranties.index') }}" class="text-sm font-medium text-brand hover:underline">All warranties</a>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Serial</th>
                    <th class="px-4 py-3">Eligibility</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($warranties as $warranty)
                    <tr class="group transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top">
                            <a href="{{ route('admin.warranties.show', $warranty) }}" class="font-mono text-[13px] font-semibold text-brand hover:underline">{{ $warranty->reference }}</a>
                        </td>
                        <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $warranty->customer?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3.5 align-top font-mono text-[13px] text-slate-600">{{ $warranty->serial_number }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $warranty->eligibility_result ?: '—' }}</td>
                        <td class="px-4 py-3.5 align-top"><x-admin.status-badge :status="$warranty->status" /></td>
                        <td class="px-4 py-3.5 align-top text-right">
                            <a href="{{ route('admin.warranties.show', $warranty) }}" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm font-medium text-slate-500 opacity-70 transition hover:bg-white hover:text-brand group-hover:opacity-100">
                                Review
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-16 text-center text-sm text-slate-500">No pending warranties.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($warranties->hasPages())
    <div class="mt-4">{{ $warranties->links() }}</div>
@endif
@endsection
