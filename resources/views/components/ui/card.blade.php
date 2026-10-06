{{--
    Tarjeta contenedora base.

    Props opcionales:
    - clickable: agrega hover con más sombra y cursor pointer (para cards que navegan o abren algo).
    - accent: pinta un borde superior de color institucional ('primary' | 'success' | 'warning' | 'danger' | 'info' | 'neutral')
              para destacar visualmente una card sobre las demás.
--}}
@props(['padding' => true, 'clickable' => false, 'accent' => null])

@php
    $accentBorders = [
        'primary' => 'border-t-primary',
        'success' => 'border-t-success',
        'warning' => 'border-t-warning-text',
        'danger' => 'border-t-danger-text',
        'info' => 'border-t-info-text',
        'neutral' => 'border-t-neutral-text',
    ];

    $classes = 'bg-white border border-line rounded-lg shadow-sm transition-shadow duration-200 '
        . ($padding ? 'p-5 ' : '')
        . ($clickable ? 'cursor-pointer hover:shadow-md hover:border-line-input ' : '')
        . ($accent ? 'border-t-4 ' . ($accentBorders[$accent] ?? '') . ' ' : '');
@endphp

<div {{ $attributes->merge(['class' => trim($classes)]) }}>
    {{ $slot }}
</div>
