{{--
    Lista de barras proporcionales (equivalente a los ".bar"/".bar i" del prototipo
    Claude Design) para indicadores tipo "cuántos de cuántos" o rankings simples
    (equipos por tipo, por sede, calidad del inventario, etc.). No es una librería
    de gráficas — son barras CSS simples, igual que en el diseño original.

    Uso ("inline": etiqueta | barra | valor, todo en una fila — para rankings):
    <x-ui.bar-list variant="inline" label-width="170px" :items="[
        ['label' => 'Palacio Naín', 'value' => 263, 'percent' => 100, 'color' => 'bg-success'],
    ]" />

    Uso ("stacked": etiqueta+valor arriba, barra ancha debajo — para indicadores de calidad):
    <x-ui.bar-list variant="stacked" :items="[
        ['label' => 'Sin código de activo', 'value' => '146 (16%)', 'percent' => 16, 'color' => 'bg-primary'],
    ]" />
--}}
@props(['items' => [], 'variant' => 'inline', 'labelWidth' => '150px'])

<div class="space-y-2.5">
    @foreach ($items as $item)
        @php $color = $item['color'] ?? 'bg-primary'; @endphp

        @if ($variant === 'stacked')
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between text-[14px]">
                    <span class="text-ink">{{ $item['label'] }}</span>
                    <span class="font-mono font-semibold text-ink">{{ $item['value'] }}</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-app-bg">
                    <div class="h-full rounded-full {{ $color }}" style="width: {{ max(2, $item['percent']) }}%"></div>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3 text-[14px]">
                <span class="shrink-0 truncate text-ink" style="width: {{ $labelWidth }}">{{ $item['label'] }}</span>
                <div class="h-3.5 flex-1 overflow-hidden rounded-full bg-app-bg">
                    <div class="h-full rounded-full {{ $color }}" style="width: {{ max(2, $item['percent']) }}%"></div>
                </div>
                <span class="w-10 shrink-0 text-right font-mono font-semibold text-ink">{{ $item['value'] }}</span>
            </div>
        @endif
    @endforeach
</div>
