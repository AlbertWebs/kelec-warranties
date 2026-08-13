@extends('layouts.public')

@section('title', 'Warranty registration & claims | K-Elec Kenya')
@section('meta_description', 'Register a K-Elec appliance warranty or file a warranty claim online. No account required to start a claim.')

@section('content')
<div class="mx-auto max-w-2xl">
    @include('public.partials.warranty-tabs', ['activeTab' => 'claim'])

    @if ($submittedClaim)
        <div class="overflow-hidden rounded-2xl border border-green-200 bg-white shadow-sm">
            <div class="border-b border-green-100 bg-gradient-to-br from-green-50 via-white to-white px-6 py-8 sm:px-8">
                <p class="text-xs font-semibold uppercase tracking-wider text-green-700">Claim submitted</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-ink">Thank you</h1>
                <p class="mt-3 text-sm leading-relaxed text-gray-600">
                    Your claim has been received. We’ll respond within one business day. Keep your claim reference for follow-up.
                </p>
            </div>
            <div class="space-y-3 px-6 py-6 sm:px-8">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Claim reference</p>
                    <p class="mt-1 font-mono text-lg font-semibold text-brand-ink">{{ $submittedClaim->reference }}</p>
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Warranty</dt>
                        <dd class="font-medium text-brand-ink">{{ $submittedClaim->warranty?->reference }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Subject</dt>
                        <dd class="font-medium text-brand-ink">{{ $submittedClaim->subject }}</dd>
                    </div>
                </dl>
                <div class="flex flex-wrap gap-2 pt-2">
                    <a href="{{ route('warranty.hub', ['tab' => 'claim']) }}" class="btn-brand !px-4 !py-2 text-sm">File another claim</a>
                    <a href="{{ route('warranty.lookup') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-brand-ink hover:border-brand hover:text-brand">Lookup warranty</a>
                </div>
            </div>
        </div>
    @elseif ($warranty)
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-br from-brand-soft via-white to-white px-6 py-8 sm:px-8">
                <p class="text-xs font-semibold uppercase tracking-wider text-brand">Step 2 of 2</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-ink">Describe the issue</h1>
                <p class="mt-3 text-sm leading-relaxed text-gray-600">
                    Filing against <span class="font-semibold text-brand-ink">{{ $warranty->reference }}</span>
                    · {{ $warranty->displayProductName() }}
                    · <span class="font-mono">{{ $warranty->serial_number }}</span>
                </p>
            </div>

            <form method="POST" action="{{ url('/warranty/claim') }}" class="space-y-4 px-6 py-6 sm:px-8">
                @csrf
                <div>
                    <label for="subject" class="mb-1 block text-sm font-medium text-brand-ink">Subject</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required maxlength="255"
                           class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                           placeholder="e.g. Not cooling / Power issue">
                    @error('subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-brand-ink">Describe the issue</label>
                    <textarea id="description" name="description" rows="5" required maxlength="5000"
                              class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                              placeholder="What happened, when it started, and any error messages.">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="customer_notes" class="mb-1 block text-sm font-medium text-brand-ink">Additional notes (optional)</label>
                    <textarea id="customer_notes" name="customer_notes" rows="3" maxlength="2000"
                              class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">{{ old('customer_notes') }}</textarea>
                    @error('customer_notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-brand w-full py-3 sm:w-auto sm:px-8">Submit claim</button>
            </form>
            <div class="border-t border-gray-100 px-6 py-4 sm:px-8">
                <form method="POST" action="{{ url('/warranty/claim/reset') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-slate-500 hover:text-brand">Use a different warranty</button>
                </form>
            </div>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gradient-to-br from-brand-soft via-white to-white px-6 py-8 text-center sm:px-8">
                <p class="text-xs font-semibold uppercase tracking-wider text-brand">Warranty claim</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-ink">File a warranty claim</h1>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-gray-600">
                    We’ll respond within one business day. No account login required — verify with your serial number and registered mobile.
                </p>
            </div>

            <form method="POST" action="{{ url('/warranty/claim/verify') }}" class="space-y-4 px-6 py-6 sm:px-8">
                @csrf
                <div>
                    <label for="serial_number" class="mb-1 block text-sm font-medium text-brand-ink">Serial number</label>
                    <input id="serial_number" name="serial_number" type="text" value="{{ old('serial_number') }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                           placeholder="Product serial"
                           autocomplete="off">
                    @error('serial_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="mobile_number" class="mb-1 block text-sm font-medium text-brand-ink">Registered mobile number</label>
                    <input id="mobile_number" name="mobile_number" type="text" value="{{ old('mobile_number') }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                           placeholder="07XXXXXXXX"
                           inputmode="tel"
                           autocomplete="tel">
                    <p class="mt-1 text-xs text-gray-500">Must match the mobile used when the warranty was registered.</p>
                    @error('mobile_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-brand w-full py-3">Continue</button>
            </form>
        </div>
    @endif
</div>
@endsection
