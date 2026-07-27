@extends('layouts.admin')
@section('title', 'Odoo Sync')
@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
<div>
<h1 class="text-2xl font-bold">Odoo Sync</h1>
<p class="text-slate-500">Connection status, logs, and retries</p>
</div>
<div class="flex gap-2">
<form method="POST" action="{{ route('admin.odoo.test') }}">@csrf<button class="rounded-lg bg-slate-900 px-4 py-2 text-white">Test connection</button></form>
<form method="POST" action="{{ route('admin.odoo.retry') }}">@csrf<button class="rounded-lg border px-4 py-2">Retry failures</button></form>
</div>
</div>
<div class="mb-6 grid gap-4 md:grid-cols-3">
<div class="rounded-xl border bg-white p-4"><div class="text-sm text-slate-500">Last success</div><div class="mt-1 font-semibold">{{ optional($lastSuccess?->created_at)->format('d M Y H:i') ?? 'Never' }}</div></div>
<div class="rounded-xl border bg-white p-4"><div class="text-sm text-slate-500">Pending failures</div><div class="mt-1 font-semibold">{{ $pendingFailures }}</div></div>
<div class="rounded-xl border bg-white p-4"><div class="text-sm text-slate-500">Mode</div><div class="mt-1 font-semibold">Mock / configured via settings</div></div>
</div>
<div class="grid gap-6 lg:grid-cols-2">
<section class="rounded-xl border bg-white p-4">
<h2 class="font-semibold">Integration logs</h2>
<ul class="mt-3 space-y-2 text-sm">
@foreach($logs as $log)
<li class="rounded bg-slate-50 px-3 py-2">{{ $log->created_at->format('d M Y H:i') }} · {{ $log->action }} · {{ $log->status }} @if($log->error_message)<span class="text-red-600">{{ Str::limit($log->error_message, 80) }}</span>@endif</li>
@endforeach
</ul>
<div class="mt-3">{{ $logs->links() }}</div>
</section>
<section class="rounded-xl border bg-white p-4">
<h2 class="font-semibold">Failures</h2>
<ul class="mt-3 space-y-2 text-sm">
@forelse($failures as $failure)
<li class="rounded bg-slate-50 px-3 py-2">{{ $failure->action }} · {{ $failure->status }} · retries {{ $failure->retry_count }}</li>
@empty
<li class="text-slate-500">No failures recorded.</li>
@endforelse
</ul>
</section>
</div>
@endsection
