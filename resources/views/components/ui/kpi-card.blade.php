{{--
    Tarjeta de indicador (KPI) para dashboards y reportes.
    Número grande en Work Sans + etiqueta pequeña debajo, con acento de color,
    ícono opcional y una micro-barra de progreso opcional para dar contexto
    (ej. "cuántos de cuántos").

    Uso simple (compatible con el uso anterior):
    <x-ui.kpi-card value="248" label="Equipos en servicio" />

    Uso con ícono + acento + progreso:
    <x-ui.kpi-card value="12" label="Pendientes por verificar" accent="warning" :progress="24" progress-label="24% del total">
        <x-slot name="icon">
            <svg>...</svg>
        </x-slot>
    </x-ui.kpi-card>

    Uso con texto secundario debajo (ej. "461 puestos · 11 sedes", como en el prototipo):
    <x-ui.kpi-card value="930" label="Total de equipos">
        <x-slot name="footer">461 puestos · 11 sedes</x-slot>
    </x-ui.kpi-card>
--}}
@props([
    'value',
    'label',
    'accent' => 'primary',
    'progress' => null,
    'progressLabel' => null,
])

@php
    $iconBg = [
        'primary' => 'bg-primary/10 text-primary',
        'success' => 'bg-success-bg text-success-text',
        'warning' => 'bg-warning-bg text-warning-text',
        'danger' => 'bg-danger-bg text-danger-text',
        'info' => 'bg-info-bg text-info-text',
        'neutral' => 'bg-neutral-bg text-neutral-text',
    ];

    $barFill = [
        'primary' => 'bg-primary',
        'success' => 'bg-success',
        'warning' => 'bg-warning-text',
        'danger' => 'bg-danger-text',
        'info' => 'bg-info-text',
        'neutral' => 'bg-neutral-text',
    ];

    $progressValue = is_null($progress) ? null : max(0, min(100, (int) $progress));
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-white p-5 shadow-sm transition-shadow duration-200 hover:shadow-md']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="font-display text-[30px] font-bold leading-tight text-ink">{{ $value }}</p>
            <p class="mt-1 text-[13px] text-ink-muted">{{ $label }}</p>
        </div>

        @isset($icon)
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $iconBg[$accent] ?? $iconBg['primary'] }}">
                {{ $icon }}
            </span>
        @endisset
    </div>

    @if (! is_null($progressValue))
        <div class="mt-3">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-app-bg">
                <div
                    class="h-full rounded-full {{ $barFill[$accent] ?? $barFill['primary'] }} transition-all duration-500 ease-out"
                    style="width: {{ $progressValue }}%"
                ></div>
            </div>

            @if ($progressLabel)
                <p class="mt-1.5 text-[13px] text-ink-muted">{{ $progressLabel }}</p>
            @endif
        </div>
    @endif

    @isset($footer)
        <p class="mt-2 text-[13px] text-ink-muted">{{ $footer }}</p>
    @endisset
</div>
