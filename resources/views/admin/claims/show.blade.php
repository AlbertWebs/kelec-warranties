@extends('layouts.admin')
@section('title', $claim->reference)
@section('content')
<a href="{{ route('admin.claims.index') }}" class="text-sm text-red-700 hover:underline">← Back to claims</a>
<h1 class="mt-2 mb-4 text-2xl font-bold">{{ $claim->reference }}</h1>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="font-semibold">Claim details</h2>
        <dl class="mt-4 space-y-2 text-sm">
            <div><span class="text-gray-500">Customer:</span> <a class="text-red-700" href="{{ route('admin.customers.show', $claim->customer) }}">{{ $claim->customer->full_name }}</a></div>
            <div><span class="text-gray-500">Warranty:</span> <a class="text-red-700" href="{{ route('admin.warranties.show', $claim->warranty) }}">{{ $claim->warranty->reference }}</a></div>
            <div><span class="text-gray-500">Product:</span> {{ $claim->warranty->displayProductName() }}</div>
            <div><span class="text-gray-500">Subject:</span> {{ $claim->subject }}</div>
            <div><span class="text-gray-500">Description:</span><p class="mt-1 whitespace-pre-wrap">{{ $claim->description }}</p></div>
            @if ($claim->customer_notes)
                <div><span class="text-gray-500">Customer notes:</span><p class="mt-1 whitespace-pre-wrap">{{ $claim->customer_notes }}</p></div>
            @endif
        </dl>
    </section>

    <section class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="font-semibold">Update claim</h2>
        <form method="POST" action="{{ route('admin.claims.update', $claim) }}" class="mt-4 space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium">Status</label>
                <select name="status" class="mt-1 w-full rounded-lg border-slate-300" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($claim->status === $status)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Admin notes</label>
                <textarea name="admin_notes" rows="5" class="mt-1 w-full rounded-lg border-slate-300">{{ old('admin_notes', $claim->admin_notes) }}</textarea>
            </div>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-white">Save</button>
        </form>
    </section>
</div>
@endsection
