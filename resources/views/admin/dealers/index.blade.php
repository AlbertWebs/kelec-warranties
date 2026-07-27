@extends('layouts.admin')
@section('title', 'Dealers')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Dealers</h1>
<form method="POST" action="{{ route('admin.dealers.store') }}" class="mb-6 grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3">
@csrf
<input name="name" required placeholder="Dealer name" class="rounded-lg border-slate-300">
<input name="dealer_code" placeholder="Code" class="rounded-lg border-slate-300">
<input name="contact_person" placeholder="Contact person" class="rounded-lg border-slate-300">
<input name="mobile_number" placeholder="Mobile" class="rounded-lg border-slate-300">
<input name="email" placeholder="Email" class="rounded-lg border-slate-300">
<input name="county" placeholder="County" class="rounded-lg border-slate-300">
<input name="town" placeholder="Town" class="rounded-lg border-slate-300">
<input name="physical_location" placeholder="Location" class="rounded-lg border-slate-300 md:col-span-2">
<button class="rounded-lg bg-red-700 px-4 py-2 text-white">Add dealer</button>
</form>
<div class="overflow-x-auto rounded-xl border bg-white">
<table class="min-w-full text-sm">
<thead class="bg-slate-50 border-b"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Code</th><th class="px-4 py-3 text-left">Contact</th><th class="px-4 py-3 text-left">Town</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3"></th></tr></thead>
<tbody>
@foreach($dealers as $dealer)
<tr class="border-b">
<td class="px-4 py-3">{{ $dealer->name }}</td>
<td class="px-4 py-3">{{ $dealer->dealer_code }}</td>
<td class="px-4 py-3">{{ $dealer->contact_person }} / {{ $dealer->mobile_number }}</td>
<td class="px-4 py-3">{{ $dealer->town }}</td>
<td class="px-4 py-3">{{ $dealer->is_active ? 'Active' : 'Inactive' }}</td>
<td class="px-4 py-3">
<form method="POST" action="{{ route('admin.dealers.destroy', $dealer) }}">@csrf @method('DELETE')
<button class="text-red-700">Delete</button></form>
</td>
</tr>
@endforeach
</tbody></table></div>
<div class="mt-4">{{ $dealers->links() }}</div>
@endsection
