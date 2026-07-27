@extends('layouts.public')

@section('title', 'My Claims')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">Customer portal</p>
        <h1 class="mt-1 text-2xl font-bold text-brand-ink sm:text-3xl">Claims</h1>
        <p class="mt-1 text-sm text-gray-600">Track service claims filed against your registered warranties.</p>
    </div>
    <a href="{{ route('customer.claims.create') }}" class="btn-brand !px-4 !py-2 text-sm">Create claim</a>
</div>

@if ($claims->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
        <p class="font-semibold text-brand-ink">No claims yet</p>
        <p class="mt-2 text-sm text-gray-600">You can create a claim only against an active warranty registered to your account.</p>
        <a href="{{ route('customer.claims.create') }}" class="btn-brand mt-6 inline-flex">Create a claim</a>
    </div>
@else
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-brand-soft/60 text-left text-xs font-semibold uppercase tracking-wide text-brand-navy">
                <tr>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Warranty</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($claims as $claim)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $claim->reference }}</td>
                        <td class="px-4 py-3">{{ $claim->warranty?->reference }}</td>
                        <td class="px-4 py-3">{{ $claim->subject }}</td>
                        <td class="px-4 py-3">{{ $claim->status->label() }}</td>
                        <td class="px-4 py-3">{{ $claim->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('customer.claims.show', $claim) }}" class="font-semibold text-brand hover:underline">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $claims->links() }}</div>
@endif
@endsection
