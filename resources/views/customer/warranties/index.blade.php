@extends('layouts.public')

@section('title', 'My Warranties')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">Customer portal</p>
        <h1 class="mt-1 text-2xl font-bold text-brand-ink sm:text-3xl">My warranties</h1>
        <p class="mt-1 text-sm text-gray-600">Hello {{ $customer->full_name }} — warranties registered to your account.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('customer.claims.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-brand-ink hover:border-brand hover:text-brand">View claims</a>
        <a href="{{ route('customer.claims.create') }}" class="btn-brand !px-4 !py-2 text-sm">Create claim</a>
    </div>
</div>

@if ($warranties->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
        <p class="text-brand-ink font-semibold">No warranties found yet</p>
        <p class="mt-2 text-sm text-gray-600">Register a product warranty, or use the same mobile/email you used when registering.</p>
        <a href="{{ route('register-warranty.create') }}" class="btn-brand mt-6 inline-flex">Register a warranty</a>
    </div>
@else
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-brand-soft/60 text-left text-xs font-semibold uppercase tracking-wide text-brand-navy">
                <tr>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Expiry</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($warranties as $warranty)
                    <tr>
                        <td class="px-4 py-3 font-medium text-brand-ink">{{ $warranty->reference }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $warranty->displayProductName() }}</div>
                            <div class="text-xs text-gray-500">{{ $warranty->serial_number }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $warranty->status->label() }}</td>
                        <td class="px-4 py-3">{{ optional($warranty->warranty_expiry_date)->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('customer.warranties.show', $warranty) }}" class="font-semibold text-brand hover:underline">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $warranties->links() }}</div>
@endif
@endsection
