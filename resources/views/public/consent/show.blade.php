@extends('layouts.public')

@section('title', 'Marketing Preference')

@section('content')
<div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-bold">Marketing communication preference</h1>
    <p class="mt-2 text-slate-600">This is optional and does not affect your warranty.</p>

    @unless($valid)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
            This link has expired or has already been used.
        </div>
    @else
        <form method="POST" action="{{ route('consent.store', $access->token) }}" class="mt-6 space-y-4">
            @csrf
            <p class="text-sm text-slate-600">Customer: {{ $access->customer?->maskedName() }}</p>
            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4">
                <input type="checkbox" name="marketing_consent" value="1" class="mt-1 rounded border-slate-300 text-red-700 focus:ring-red-600">
                <span>I would like to receive marketing communication from K-Elec. Leave unticked if you prefer not to.</span>
            </label>
            <button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Save preference</button>
        </form>
    @endunless
</div>
@endsection
