{{--
    Badge / etiqueta de estado tipo píldora.

    Uso directo:      <x-ui.badge variant="success">En servicio</x-ui.badge>
    Auto-mapeo desde texto de estado (útil para datos que vienen de BD):
                       <x-ui.badge :status="$equipo->estado" />
--}}
@props(['variant' => null, 'status' => null])

@php
    $statusMap = [
        'en servicio' => 'success',
        'activo' => 'success',
        'disponible' => 'success',
        'sin asignar' => 'neutral',
        'en bodega' => 'neutral',
        'dado de baja' => 'danger',
        'de baja' => 'danger',
        'pendiente de verificar' => 'warning',
        'pendiente de firma' => 'warning',
        'en mantenimiento' => 'warning',
    ];

    $resolved = $variant
        ?? ($status ? ($statusMap[mb_strtolower(trim($status))] ?? 'neutral') : 'neutral');

    $styles = [
        'success' => 'bg-success-bg text-success-text',
        'warning' => 'bg-warning-bg text-warning-text',
        'danger' => 'bg-danger-bg text-danger-text',
        'info' => 'bg-info-bg text-info-text',
        'neutral' => 'bg-neutral-bg text-neutral-text',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-full px-3 py-1 text-[13px] font-semibold ring-1 ring-inset ring-black/5 transition-colors ' . ($styles[$resolved] ?? $styles['neutral'])]) }}>
    {{ $status ?? $slot }}
</span>
