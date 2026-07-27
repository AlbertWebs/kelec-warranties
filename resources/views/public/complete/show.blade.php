@extends('layouts.public')

@section('title', 'Complete Warranty Details')

@section('content')
<div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-bold">Complete your warranty details</h1>
    @if ($warranty)
        <p class="mt-2 text-slate-600">Reference {{ $warranty->reference }} · {{ $warranty->displayProductName() }} · {{ $warranty->serial_number }}</p>
    @endif

    @unless($valid)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
            This completion link has expired or has already been used.
        </div>
    @else
        <form method="POST" action="{{ route('complete-registration.store', $access->token) }}" class="mt-6 grid gap-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium">Full name</label>
                <input name="full_name" value="{{ old('full_name', $access->customer?->full_name) }}" required class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Mobile number</label>
                <input name="mobile_number" value="{{ old('mobile_number') }}" required class="w-full rounded-lg border-slate-300" placeholder="07XXXXXXXX">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Email (optional)</label>
                <input type="email" name="email" value="{{ old('email', $access->customer?->email) }}" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">County (optional)</label>
                <input name="county" value="{{ old('county') }}" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Town (optional)</label>
                <input name="town" value="{{ old('town') }}" class="w-full rounded-lg border-slate-300">
            </div>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="marketing_consent" value="1" class="mt-1 rounded border-slate-300 text-red-700">
                <span>Optional: receive marketing communication from K-Elec (unticked by default).</span>
            </label>
            <button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Save and activate</button>
        </form>
    @endunless
</div>
@endsection
