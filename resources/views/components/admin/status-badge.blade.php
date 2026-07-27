@props([
    'status',
])

<span {{ $attributes->class([
    'inline-flex items-center whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset',
    $status->badgeClasses(),
]) }}>
    {{ $status->label() }}
</span>
