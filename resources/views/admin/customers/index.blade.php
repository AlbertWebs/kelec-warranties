@extends('layouts.admin')
@section('title', 'Customers')
@section('content')
<div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Customers</h1>
    <form method="GET"><input name="q" value="{{ request('q') }}" placeholder="Search customers" class="rounded-lg border-slate-300"></form>
</div>
<div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
<table class="min-w-full text-sm">
<thead class="border-b bg-slate-50 text-slate-500"><tr>
<th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Mobile</th><th class="px-4 py-3 text-left">Email</th><th class="px-4 py-3 text-left">Warranties</th><th class="px-4 py-3"></th>
</tr></thead>
<tbody>
@forelse($customers as $customer)
<tr class="border-b">
<td class="px-4 py-3">{{ $customer->full_name }} @if($customer->possible_duplicate)<span class="text-xs text-amber-600">Possible duplicate</span>@endif</td>
<td class="px-4 py-3">{{ $customer->mobile_normalized }}</td>
<td class="px-4 py-3">{{ $customer->email }}</td>
<td class="px-4 py-3">{{ $customer->warranties_count }} / {{ $customer->active_warranties_count }} active</td>
<td class="px-4 py-3"><a class="text-red-700" href="{{ route('admin.customers.show', $customer) }}">View</a></td>
</tr>
@empty<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No customers found.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-4">{{ $customers->links() }}</div>
@endsection
