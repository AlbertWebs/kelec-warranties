@extends('layouts.admin')

@section('title', 'Warranties')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">Warranties</h1>
        <p class="text-slate-500">Search and manage warranty registrations</p>
    </div>
    @can('export', App\Models\Warranty::class)
        <a href="{{ route('admin.warranties.export', request()->query()) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Export CSV</a>
    @endcan
</div>

<form method="GET" class="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, phone, serial, reference..." class="rounded-lg border-slate-300 md:col-span-2">
    <select name="status" class="rounded-lg border-slate-300">
        <option value="">All statuses</option>
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <select name="purchase_source_id" class="rounded-lg border-slate-300">
        <option value="">All sources</option>
        @foreach ($purchaseSources as $source)
            <option value="{{ $source->id }}" @selected(($filters['purchase_source_id'] ?? '') == $source->id)>{{ $source->name }}</option>
        @endforeach
    </select>
    <button class="rounded-lg bg-red-700 px-4 py-2 text-white md:col-span-4 md:w-fit">Filter</button>
</form>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="border-b bg-slate-50 text-slate-500">
            <tr>
                <th class="px-4 py-3">Reference</th>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Phone</th>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3">Serial</th>
                <th class="px-4 py-3">Source</th>
                <th class="px-4 py-3">Expiry</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($warranties as $warranty)
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-3 font-medium">{{ $warranty->reference }}</td>
                    <td class="px-4 py-3">{{ $warranty->customer?->full_name }}</td>
                    <td class="px-4 py-3">{{ $warranty->customer?->mobile_normalized }}</td>
                    <td class="px-4 py-3">{{ $warranty->displayProductName() }}</td>
                    <td class="px-4 py-3">{{ $warranty->serial_number }}</td>
                    <td class="px-4 py-3">{{ $warranty->purchaseSource?->name }}</td>
                    <td class="px-4 py-3">{{ optional($warranty->warranty_expiry_date)->format('d M Y') }}</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs">{{ $warranty->status->label() }}</span></td>
                    <td class="px-4 py-3"><a href="{{ route('admin.warranties.show', $warranty) }}" class="text-red-700 hover:underline">View</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">No warranties found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $warranties->links() }}</div>
@endsection
