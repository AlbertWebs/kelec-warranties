@php
    $isActive = $warranty->status === \App\Enums\WarrantyStatus::Active;
    $isPending = in_array($warranty->status, [
        \App\Enums\WarrantyStatus::PendingVerification,
        \App\Enums\WarrantyStatus::Submitted,
        \App\Enums\WarrantyStatus::UnderReview,
    ], true);
@endphp

<div class="rounded-xl border border-brand/20 bg-brand-soft/80 p-4 sm:p-5" x-data="{ copied: false }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider text-brand-navy">Warranty reference</p>
            <p class="mt-1 break-all font-mono text-xl font-bold tracking-wide text-brand-ink sm:text-2xl">{{ $warranty->reference }}</p>
            @isset($referenceHint)
                <p class="mt-1 text-sm text-gray-600">{{ $referenceHint }}</p>
            @endisset
        </div>
        <button type="button"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark"
                @click="navigator.clipboard.writeText(@js($warranty->reference)); copied = true; setTimeout(() => copied = false, 2000)">
            <span x-text="copied ? 'Copied' : 'Copy reference'"></span>
        </button>
    </div>
</div>

<dl class="mt-6 grid gap-3 sm:grid-cols-2">
    <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-4">
        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</dt>
        <dd class="mt-1">
            <span @class([
                'inline-flex rounded-md px-2.5 py-1 text-sm font-semibold',
                'bg-green-100 text-green-800' => $isActive,
                'bg-amber-100 text-amber-800' => $isPending,
                'bg-gray-100 text-gray-800' => ! $isActive && ! $isPending,
            ])>{{ $warranty->status->label() }}</span>
        </dd>
    </div>

    @foreach ($fields as $field)
        <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $field['label'] }}</dt>
            <dd class="mt-1 font-semibold text-brand-ink {{ ($field['mono'] ?? false) ? 'font-mono text-sm' : '' }}">
                {{ $field['value'] }}
                @isset($field['sub'])
                    <span class="block text-sm font-normal text-gray-600">{{ $field['sub'] }}</span>
                @endisset
            </dd>
        </div>
    @endforeach
</dl>
