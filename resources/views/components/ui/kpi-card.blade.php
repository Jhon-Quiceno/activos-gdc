{{--
    Tarjeta de indicador (KPI) para dashboards y reportes.
    Número grande en Work Sans + etiqueta pequeña debajo.

    Uso:
    <x-ui.kpi-card value="248" label="Equipos en servicio" />
--}}
@props(['value', 'label'])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-white p-5']) }}>
    <p class="font-display text-3xl font-bold text-ink">{{ $value }}</p>
    <p class="mt-1 text-sm text-ink-muted">{{ $label }}</p>
</div>
