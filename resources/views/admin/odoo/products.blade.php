@extends('layouts.admin')

@section('title', 'Odoo Product Sync')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="admin-page-title">Odoo Product Sync</h1>
        <p class="admin-page-subtitle">Import, synchronize, monitor, and retry Odoo product records.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.odoo.products.sync') }}" onsubmit="return confirm('Full synchronization may take time depending on product volume in Odoo. Continue?');">
            @csrf
            <input type="hidden" name="sync_type" value="full">
            <input type="hidden" name="confirm_full" value="yes">
            <button class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Sync Products from Odoo</button>
        </form>
        <form method="POST" action="{{ route('admin.odoo.products.sync') }}">
            @csrf
            <input type="hidden" name="sync_type" value="incremental">
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-brand-ink hover:border-brand hover:text-brand">Run Incremental Sync</button>
        </form>
        <form method="POST" action="{{ route('admin.odoo.products.retry-pending') }}">
            @csrf
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-brand-ink hover:border-brand hover:text-brand">Retry Failed Records</button>
        </form>
    </div>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-5">
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <div class="text-xs uppercase tracking-wide text-slate-500">Last Sync</div>
        <div class="mt-1 text-sm font-semibold text-brand-ink">{{ optional($stats['last_sync_at'])->format('d M Y H:i') ?? 'Never' }}</div>
    </div>
    <a href="{{ route('admin.products.index', ['source' => 'odoo']) }}" class="rounded-xl border bg-white p-4 shadow-sm transition hover:border-brand hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brand/30">
        <div class="text-xs uppercase tracking-wide text-slate-500">Imported</div>
        <div class="mt-1 text-lg font-semibold text-brand-ink">{{ number_format($stats['imported']) }}</div>
        <div class="mt-1 text-xs font-medium text-brand">View Odoo products →</div>
    </a>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <div class="text-xs uppercase tracking-wide text-slate-500">Updated</div>
        <div class="mt-1 text-lg font-semibold text-brand-ink">{{ number_format($stats['updated']) }}</div>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <div class="text-xs uppercase tracking-wide text-slate-500">Failed</div>
        <div class="mt-1 text-lg font-semibold text-red-600">{{ number_format($stats['failed']) }}</div>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <div class="text-xs uppercase tracking-wide text-slate-500">Current Status</div>
        <div class="mt-1 text-sm font-semibold text-brand-ink">{{ str_replace('_', ' ', ucfirst($stats['status'])) }}</div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="admin-table-wrap">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">Synchronization Runs</div>
        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Started</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Processed</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ optional($run->started_at)->format('d M Y H:i') ?? '—' }}</td>
                            <td class="capitalize">{{ $run->sync_type }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($run->status)) }}</td>
                            <td>{{ number_format($run->processed_records) }} / {{ number_format($run->total_records) }}</td>
                            <td class="text-xs text-slate-600">
                                +{{ $run->created_records }} new · ~{{ $run->updated_records }} updated · -{{ $run->failed_records }} failed
                                @if($run->error_message)
                                    <div class="mt-1 text-red-600">{{ \Illuminate\Support\Str::limit($run->error_message, 90) }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="admin-table-empty">No product sync runs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($runs->hasPages())
            <div class="border-t border-slate-100 p-3">{{ $runs->links() }}</div>
        @endif
    </section>

    <section class="admin-table-wrap">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">Failed Records & API Errors</div>
        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Identifier</th>
                        <th>Error</th>
                        <th>Retries</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($failures as $failure)
                        <tr>
                            <td class="admin-table-mono">{{ $failure->identifier ?: ($failure->external_id ?: '—') }}</td>
                            <td class="text-xs text-red-600">{{ \Illuminate\Support\Str::limit($failure->error_message, 100) }}</td>
                            <td>{{ $failure->retry_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.odoo.products.retry-failure', $failure) }}">
                                    @csrf
                                    <button class="admin-table-action">Retry</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="admin-table-empty">No failed records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

