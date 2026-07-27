@extends('layouts.admin')
@section('title', 'Edit Product')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Edit product</h1>
<form method="POST" action="{{ route('admin.products.update', $product) }}" class="max-w-2xl space-y-3 rounded-xl border bg-white p-4 shadow-sm">
@csrf @method('PUT')
@include('admin.products._form', ['product' => $product])
<button class="rounded-lg bg-red-700 px-4 py-2 text-white">Update</button>
</form>
@endsection
