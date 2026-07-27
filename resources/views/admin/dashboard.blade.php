@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="text-slate-500">Warranty operations overview</p>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @foreach ([
        ['Total', $stats['total']],
        ['Active', $stats['active']],
        ['Pending', $stats['pending']],
        ['Rejected', $stats['rejected']],
        ['Expired', $stats['expired']],
        ['Today', $stats['today']],
        ['This month', $stats['month']],
        ['Odoo success %', $stats['odoo_success_rate']],
        ['Odoo failures', $stats['odoo_failures']],
        ['SMS/Email fail', $stats['sms_failures'].'/'.$stats['email_failures']],
    ] as [$label, $value])
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">{{ $label }}</div>
            <div class="mt-2 text-2xl font-bold">{{ $value }}</div>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="font-semibold">Registrations by month</h2>
        <ul class="mt-3 space-y-2 text-sm">
            @forelse ($registrationsByMonth as $month => $total)
                <li class="flex justify-between"><span>{{ $month }}</span><span class="font-medium">{{ $total }}</span></li>
            @empty
                <li class="text-slate-500">No registrations yet.</li>
            @endforelse
        </ul>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="font-semibold">By purchase source</h2>
        <ul class="mt-3 space-y-2 text-sm">
            @forelse ($bySource as $row)
                <li class="flex justify-between"><span>{{ $row->purchaseSource?->name ?? 'Unknown' }}</span><span class="font-medium">{{ $row->total }}</span></li>
            @empty
                <li class="text-slate-500">No data yet.</li>
            @endforelse
        </ul>
    </div>
</div>

<div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <h2 class="font-semibold">Recent activity</h2>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b text-slate-500">
                <tr>
                    <th class="py-2 pr-4">Reference</th>
                    <th class="py-2 pr-4">Customer</th>
                    <th class="py-2 pr-4">Product</th>
                    <th class="py-2 pr-4">Status</th>
                    <th class="py-2">Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recent as $warranty)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 pr-4"><a class="text-red-700 hover:underline" href="{{ route('admin.warranties.show', $warranty) }}">{{ $warranty->reference }}</a></td>
                        <td class="py-2 pr-4">{{ $warranty->customer?->full_name }}</td>
                        <td class="py-2 pr-4">{{ $warranty->displayProductName() }}</td>
                        <td class="py-2 pr-4">{{ $warranty->status->label() }}</td>
                        <td class="py-2">{{ $warranty->created_at?->format('d M Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
