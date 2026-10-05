@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'bg-white border border-line rounded-lg ' . ($padding ? 'p-5' : '')]) }}>
    {{ $slot }}
</div>
