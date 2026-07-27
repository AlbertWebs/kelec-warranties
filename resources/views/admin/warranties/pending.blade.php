@extends('layouts.admin')

@section('title', 'Pending Verification')

@section('content')
<div class="mb-4">
    <h1 class="text-2xl font-bold">Pending Verification</h1>
    <p class="text-slate-500">Registrations awaiting manual review</p>
</div>

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="border-b bg-slate-50 text-slate-500">
            <tr>
                <th class="px-4 py-3">Reference</th>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Serial</th>
                <th class="px-4 py-3">Eligibility</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($warranties as $warranty)
                <tr class="border-b">
                    <td class="px-4 py-3">{{ $warranty->reference }}</td>
                    <td class="px-4 py-3">{{ $warranty->customer?->full_name }}</td>
                    <td class="px-4 py-3">{{ $warranty->serial_number }}</td>
                    <td class="px-4 py-3">{{ $warranty->eligibility_result }}</td>
                    <td class="px-4 py-3">{{ $warranty->status->label() }}</td>
                    <td class="px-4 py-3"><a class="text-red-700 hover:underline" href="{{ route('admin.warranties.show', $warranty) }}">Review</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No pending warranties.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $warranties->links() }}</div>
@endsection
