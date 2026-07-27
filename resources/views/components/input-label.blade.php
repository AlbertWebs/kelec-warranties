@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm leading-tight text-brand-navy']) }}>
    {{ $value ?? $slot }}
</label>
