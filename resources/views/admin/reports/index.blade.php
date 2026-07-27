@extends('layouts.admin')
@section('title', 'Reports')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Reports</h1>
<div class="grid gap-4 md:grid-cols-4">
@foreach($summary as $label => $value)
<div class="rounded-xl border bg-white p-4 shadow-sm">
<div class="text-sm capitalize text-slate-500">{{ str_replace('_', ' ', $label) }}</div>
<div class="mt-2 text-2xl font-bold">{{ $value }}</div>
</div>
@endforeach
</div>
<p class="mt-6 text-sm text-slate-600">Use the warranties list filters and Export CSV for filtered warranty extracts. Queued XLSX/PDF exports expand in Phase 5.</p>
<a href="{{ route('admin.warranties.export') }}" class="mt-4 inline-flex rounded-lg bg-red-700 px-4 py-2 text-white">Export all warranties CSV</a>
@endsection
