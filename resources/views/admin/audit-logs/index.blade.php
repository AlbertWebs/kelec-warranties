@extends('layouts.admin')
@section('title', 'Audit Logs')
@section('content')
<div class="mb-4 flex items-center justify-between">
<h1 class="text-2xl font-bold">Audit Logs</h1>
<form method="GET"><input name="q" value="{{ request('q') }}" class="rounded-lg border-slate-300" placeholder="Search actions"></form>
</div>
<div class="overflow-x-auto rounded-xl border bg-white">
<table class="min-w-full text-sm">
<thead class="bg-slate-50 border-b"><tr><th class="px-4 py-3 text-left">When</th><th class="px-4 py-3 text-left">User</th><th class="px-4 py-3 text-left">Action</th><th class="px-4 py-3 text-left">Entity</th><th class="px-4 py-3 text-left">IP</th></tr></thead>
<tbody>
@foreach($logs as $log)
<tr class="border-b">
<td class="px-4 py-3">{{ $log->created_at->format('d M Y H:i') }}</td>
<td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
<td class="px-4 py-3">{{ $log->action }}</td>
<td class="px-4 py-3">{{ class_basename((string)$log->entity_type) }} #{{ $log->entity_id }}</td>
<td class="px-4 py-3">{{ $log->ip_address }}</td>
</tr>
@endforeach
</tbody></table></div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
