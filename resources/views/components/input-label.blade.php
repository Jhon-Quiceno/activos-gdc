@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-ink-label']) }}>
    {{ $value ?? $slot }}
</label>
