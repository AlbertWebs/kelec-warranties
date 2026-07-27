@extends('layouts.admin')
@section('title', $customer->full_name)
@section('content')
<h1 class="mb-4 text-2xl font-bold">{{ $customer->full_name }}</h1>
<div class="grid gap-6 lg:grid-cols-2">
<section class="rounded-xl border bg-white p-4 shadow-sm">
<h2 class="font-semibold">Customer details</h2>
<form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="mt-4 grid gap-3">
@csrf @method('PUT')
<input name="full_name" value="{{ $customer->full_name }}" class="rounded-lg border-slate-300" required>
<input name="mobile_number" value="{{ $customer->mobile_number }}" class="rounded-lg border-slate-300" required>
<input name="email" value="{{ $customer->email }}" class="rounded-lg border-slate-300">
<input name="county" value="{{ $customer->county }}" class="rounded-lg border-slate-300" placeholder="County">
<input name="town" value="{{ $customer->town }}" class="rounded-lg border-slate-300" placeholder="Town">
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="marketing_consent" value="1" @checked($customer->marketing_consent)> Marketing consent</label>
<button class="rounded-lg bg-slate-900 px-4 py-2 text-white w-fit">Save</button>
</form>
</section>
<section class="rounded-xl border bg-white p-4 shadow-sm">
<h2 class="font-semibold">Warranties</h2>
<ul class="mt-3 space-y-2 text-sm">
@foreach($customer->warranties as $warranty)
<li><a class="text-red-700" href="{{ route('admin.warranties.show', $warranty) }}">{{ $warranty->reference }}</a> · {{ $warranty->status->label() }}</li>
@endforeach
</ul>
</section>
</div>
@endsection
