@extends('layouts.admin')
@section('title', 'Notifications')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Notifications</h1>
<div class="mb-6 rounded-xl border bg-white p-4">
<h2 class="font-semibold">Templates</h2>
<ul class="mt-3 space-y-2 text-sm">
@foreach($templates as $template)
<li>{{ $template->name }} · {{ $template->key }} · {{ $template->channel->value }} · {{ $template->is_active ? 'Active' : 'Inactive' }}</li>
@endforeach
</ul>
</div>
<div class="overflow-x-auto rounded-xl border bg-white">
<table class="min-w-full text-sm">
<thead class="bg-slate-50 border-b"><tr><th class="px-4 py-3 text-left">When</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Channel</th><th class="px-4 py-3 text-left">Recipient</th><th class="px-4 py-3 text-left">Status</th></tr></thead>
<tbody>
@foreach($logs as $log)
<tr class="border-b">
<td class="px-4 py-3">{{ $log->created_at->format('d M Y H:i') }}</td>
<td class="px-4 py-3">{{ $log->notification_type }}</td>
<td class="px-4 py-3">{{ $log->channel->value }}</td>
<td class="px-4 py-3">{{ $log->recipient }}</td>
<td class="px-4 py-3">{{ $log->status }}</td>
</tr>
@endforeach
</tbody></table></div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
