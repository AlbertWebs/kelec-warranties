@extends('layouts.admin')

@section('title', 'Product Categories')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-brand-ink">Product Categories</h1>
    <p class="mt-1 text-sm text-slate-500">Default warranty periods by category</p>
</div>

<form method="POST" action="{{ route('admin.product-categories.store') }}" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
    @csrf
    <input name="name" placeholder="Name" required class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="code" placeholder="Code" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input type="number" name="default_warranty_months" value="12" required class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <button class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Add category</button>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Default months</th>
                    <th class="px-4 py-3">Products</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $category)
                    <tr class="group transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $category->name }}</td>
                        <td class="px-4 py-3.5 align-top font-mono text-[13px] text-slate-600">{{ $category->code ?: '—' }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $category->default_warranty_months }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $category->products_count }}</td>
                        <td class="px-4 py-3.5 align-top text-right">
                            <form method="POST" action="{{ route('admin.product-categories.destroy', $category) }}" onsubmit="return confirm('Delete category?')">
                                @csrf @method('DELETE')
                                <button class="inline-flex rounded-md px-2 py-1 text-sm font-medium text-red-600 transition hover:bg-red-50">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-16 text-center text-sm text-slate-500">No categories found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($categories->hasPages())
    <div class="mt-4">{{ $categories->links() }}</div>
@endif
@endsection
