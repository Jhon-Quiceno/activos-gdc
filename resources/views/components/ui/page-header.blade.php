{{--
    Encabezado de página: título grande (26px, como en el prototipo) + subtítulo
    opcional + acciones a la derecha (botones). Va como primer elemento del
    contenido de cada pantalla, dentro de <main>, NO en la barra superior
    (la barra superior solo tiene búsqueda, notificaciones y usuario).

    Uso:
    <x-ui.page-header title="Equipos" subtitle="930 equipos registrados">
        <x-slot name="actions">
            <x-ui.button variant="secondary">Exportar a Excel</x-ui.button>
        </x-slot>
    </x-ui.page-header>
--}}
@props(['title', 'subtitle' => null])

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-1.5 text-ink-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-3">{{ $actions }}</div>
    @endisset
</div>
