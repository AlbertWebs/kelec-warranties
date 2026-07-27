@extends('layouts.public')

@section('title', 'Create Claim')

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('customer.claims.index') }}" class="text-sm font-medium text-brand hover:underline">← Back to claims</a>
    <h1 class="mt-2 text-2xl font-bold text-brand-ink sm:text-3xl">Create claim</h1>
    <p class="mt-1 text-sm text-gray-600">Claims can only be filed against your active registered warranties.</p>

    @if ($warranties->isEmpty())
        <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 px-5 py-6 text-sm text-amber-900">
            You do not have an active warranty eligible for a claim.
            <a href="{{ route('customer.warranties.index') }}" class="font-semibold underline">View your warranties</a>
        </div>
    @else
        <form method="POST" action="{{ route('customer.claims.store') }}" class="mt-8 space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="warranty_id" class="block text-sm font-medium text-brand-ink">Warranty</label>
                <select id="warranty_id" name="warranty_id" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                    <option value="">Select a warranty</option>
                    @foreach ($warranties as $warranty)
                        <option value="{{ $warranty->id }}" @selected((string) old('warranty_id', request('warranty_id')) === (string) $warranty->id)>
                            {{ $warranty->reference }} — {{ $warranty->displayProductName() }} ({{ $warranty->serial_number }})
                        </option>
                    @endforeach
                </select>
                @error('warranty_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="subject" class="block text-sm font-medium text-brand-ink">Subject</label>
                <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required maxlength="255"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                @error('subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-brand-ink">Describe the issue</label>
                <textarea id="description" name="description" rows="5" required maxlength="5000"
                          class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="customer_notes" class="block text-sm font-medium text-brand-ink">Additional notes (optional)</label>
                <textarea id="customer_notes" name="customer_notes" rows="3" maxlength="2000"
                          class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand">{{ old('customer_notes') }}</textarea>
                @error('customer_notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-brand">Submit claim</button>
        </form>
    @endif
</div>
@endsection
