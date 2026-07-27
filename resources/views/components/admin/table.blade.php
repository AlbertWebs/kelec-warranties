@props([
    'empty' => false,
])

<div {{ $attributes->class(['admin-table-wrap']) }}>
    <div class="admin-table-scroll">
        {{ $slot }}
    </div>
</div>
