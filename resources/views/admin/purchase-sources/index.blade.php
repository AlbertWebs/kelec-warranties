@extends('layouts.admin')
@section('title', 'Purchase Sources')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Purchase Sources</h1>
<form method="POST" action="{{ route('admin.purchase-sources.store') }}" class="mb-6 grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-4">
@csrf
<input name="name" required placeholder="Name" class="rounded-lg border-slate-300">
<input name="code" required placeholder="Code" class="rounded-lg border-slate-300">
<select name="type" class="rounded-lg border-slate-300">
@foreach($types as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach
</select>
<button class="rounded-lg bg-red-700 px-4 py-2 text-white">Add</button>
</form>
<div class="overflow-x-auto rounded-xl border bg-white">
<table class="min-w-full text-sm">
<thead class="bg-slate-50 border-b"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Code</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Active</th></tr></thead>
<tbody>
@foreach($purchaseSources as $source)
<tr class="border-b">
<td class="px-4 py-3">{{ $source->name }}</td>
<td class="px-4 py-3">{{ $source->code }}</td>
<td class="px-4 py-3">{{ $source->type->label() }}</td>
<td class="px-4 py-3">{{ $source->is_active ? 'Yes' : 'No' }}</td>
</tr>
@endforeach
</tbody></table></div>
@endsection
