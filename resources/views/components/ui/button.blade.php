@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary-hover focus-visible:outline-primary',
        'success' => 'bg-success text-white hover:brightness-95 focus-visible:outline-success',
        'danger' => 'bg-danger-text text-white hover:bg-danger-hover focus-visible:outline-danger-hover',
        'secondary' => 'bg-white text-ink-label border border-line-input hover:bg-app-bg focus-visible:outline-primary',
        'ghost' => 'bg-transparent text-primary hover:bg-info-bg focus-visible:outline-primary',
    ];

    $sizes = [
        'md' => 'h-11 px-5 text-sm',
        'sm' => 'h-9 px-3.5 text-sm',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition '
        . 'disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 '
        . ($variants[$variant] ?? $variants['primary']) . ' '
        . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
