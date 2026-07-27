@extends('layouts.admin')
@section('title', 'Product Categories')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Product Categories</h1>
<form method="POST" action="{{ route('admin.product-categories.store') }}" class="mb-6 grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-4">
@csrf
<input name="name" placeholder="Name" required class="rounded-lg border-slate-300">
<input name="code" placeholder="Code" class="rounded-lg border-slate-300">
<input type="number" name="default_warranty_months" value="12" required class="rounded-lg border-slate-300">
<button class="rounded-lg bg-red-700 px-4 py-2 text-white">Add</button>
</form>
<div class="overflow-x-auto rounded-xl border bg-white">
<table class="min-w-full text-sm">
<thead class="bg-slate-50 border-b"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Code</th><th class="px-4 py-3 text-left">Default months</th><th class="px-4 py-3 text-left">Products</th><th class="px-4 py-3"></th></tr></thead>
<tbody>
@foreach($categories as $category)
<tr class="border-b">
<td class="px-4 py-3">{{ $category->name }}</td>
<td class="px-4 py-3">{{ $category->code }}</td>
<td class="px-4 py-3">{{ $category->default_warranty_months }}</td>
<td class="px-4 py-3">{{ $category->products_count }}</td>
<td class="px-4 py-3">
<form method="POST" action="{{ route('admin.product-categories.destroy', $category) }}" onsubmit="return confirm('Delete category?')">@csrf @method('DELETE')
<button class="text-red-700">Delete</button></form>
</td>
</tr>
@endforeach
</tbody></table></div>
<div class="mt-4">{{ $categories->links() }}</div>
@endsection
