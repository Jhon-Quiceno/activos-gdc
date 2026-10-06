@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[13px] font-semibold text-ink-label']) }}>
    {{ $value ?? $slot }}
</label>
