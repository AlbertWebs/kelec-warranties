@extends('layouts.admin')
@section('title', 'Create Product')
@section('content')
<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="admin-page-title">Create Product</h1>
        <p class="admin-page-subtitle">Add a product to the warranty catalog with complete identifiers and warranty behavior.</p>
    </div>
    <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-brand hover:underline">Back to products</a>
</div>

<form method="POST" action="{{ route('admin.products.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
    @csrf
    @include('admin.products._form', ['product' => null])

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-4">
        <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</a>
        <button class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-dark">Create Product</button>
    </div>
</form>
@endsection
