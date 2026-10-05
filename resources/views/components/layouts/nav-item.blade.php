{{--
    Ítem del menú lateral: ícono (idéntico al prototipo de Claude Design) + etiqueta
    + barra de acento animada cuando está activo.

    Uso: <x-layouts.nav-item :item="['label' => 'Equipos', 'route' => 'equipos.index', 'pattern' => 'equipos.*', 'icon' => 'equipos']" />
--}}
@props(['item'])

@php
    $isActive = request()->routeIs($item['pattern']);
@endphp

<li>
    <a
        href="{{ route($item['route']) }}"
        wire:navigate
        class="group relative flex items-center gap-3 overflow-hidden rounded-lg px-3 py-2.5 text-[15px] transition-colors duration-150
            {{ $isActive ? 'bg-navy-active font-semibold text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
    >
        <span
            class="absolute inset-y-1 left-0 w-1 rounded-r-full bg-white transition-transform duration-200 ease-out
                {{ $isActive ? 'scale-y-100' : 'scale-y-0' }}"
        ></span>

        <span class="shrink-0 {{ $isActive ? 'text-white' : 'text-white/70 group-hover:text-white' }}">
            <x-layouts.nav-icon :name="$item['icon']" />
        </span>

        {{ $item['label'] }}
    </a>
</li>
