@extends('layouts.admin')
@section('title', 'Products')
@section('content')
<div class="mb-4 flex items-center justify-between">
<h1 class="text-2xl font-bold">Products</h1>
@can('products.manage')<a href="{{ route('admin.products.create') }}" class="rounded-lg bg-red-700 px-4 py-2 text-white">Add product</a>@endcan
</div>
<form method="GET" class="mb-4"><input name="q" value="{{ request('q') }}" class="rounded-lg border-slate-300" placeholder="Search products"></form>
<div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
<table class="min-w-full text-sm">
<thead class="bg-slate-50 text-slate-500 border-b"><tr>
<th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">SKU</th><th class="px-4 py-3 text-left">Model</th><th class="px-4 py-3 text-left">Category</th><th class="px-4 py-3 text-left">Warranty</th><th class="px-4 py-3 text-left">Source</th><th class="px-4 py-3"></th>
</tr></thead>
<tbody>
@foreach($products as $product)
<tr class="border-b">
<td class="px-4 py-3">{{ $product->name }}</td>
<td class="px-4 py-3">{{ $product->sku }}</td>
<td class="px-4 py-3">{{ $product->model }}</td>
<td class="px-4 py-3">{{ $product->category?->name }}</td>
<td class="px-4 py-3">{{ $product->resolvedWarrantyMonths() }} months</td>
<td class="px-4 py-3">{{ $product->is_odoo_managed ? 'Odoo sync' : 'Local' }}</td>
<td class="px-4 py-3"><a href="{{ route('admin.products.edit', $product) }}" class="text-red-700">Edit</a></td>
</tr>
@endforeach
</tbody></table></div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
