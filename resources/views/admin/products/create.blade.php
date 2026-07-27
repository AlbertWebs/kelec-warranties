@extends('layouts.admin')
@section('title', 'Create Product')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Create product</h1>
<form method="POST" action="{{ route('admin.products.store') }}" class="max-w-2xl space-y-3 rounded-xl border bg-white p-4 shadow-sm">
@csrf
@include('admin.products._form', ['product' => null])
<button class="rounded-lg bg-red-700 px-4 py-2 text-white">Create</button>
</form>
@endsection
