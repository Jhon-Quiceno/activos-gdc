{{--
    Búsqueda y filtros de equipos (trait App\Livewire\Equipos\Concerns\FiltrosDeEquipos).
    La usan el listado de Equipos y el de Traslados, dentro del slot «filters» de <x-ui.table>.

    Variables: $equipos (paginador), $sedes, $dependencias, $tipos y, opcional,
    $conDadosDeBaja (false en Traslados, que nunca muestra equipos dados de baja).
--}}
<div class="relative w-full max-w-sm">
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
    </svg>
    <input
        type="text"
        wire:model.live.debounce.400ms="busqueda"
        placeholder="{{ __('Buscar por serial, código, responsable, cédula, dependencia o sede...') }}"
        class="h-11 w-full rounded-lg border-line-input pl-9 text-[14px] text-ink placeholder:text-ink-muted focus:border-primary focus:ring-primary"
    >
</div>

<span class="ml-auto text-[14px] text-ink-muted">
    {{ trans_choice(':count equipo|:count equipos', $equipos->total(), ['count' => $equipos->total()]) }}
</span>

@php
    $claseFiltro = 'h-10 rounded-lg border-line-input text-[13px] text-ink focus:border-primary focus:ring-primary';
@endphp
<div class="flex w-full flex-wrap items-center gap-2">
    <select wire:model.live="responsable" class="{{ $claseFiltro }}" aria-label="{{ __('Responsable') }}">
        <option value="">{{ __('Responsable: todos') }}</option>
        <option value="con">{{ __('Con responsable') }}</option>
        <option value="sin">{{ __('Sin responsable') }}</option>
    </select>

    <select wire:model.live="sede" class="{{ $claseFiltro }}" aria-label="{{ __('Sede') }}">
        <option value="">{{ __('Sede: todas') }}</option>
        @foreach ($sedes as $opcion)
            <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
        @endforeach
    </select>

    <select wire:model.live="dependencia" class="{{ $claseFiltro }} max-w-[220px]" aria-label="{{ __('Dependencia') }}">
        <option value="">{{ __('Dependencia: todas') }}</option>
        @foreach ($dependencias as $opcion)
            <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
        @endforeach
    </select>

    <select wire:model.live="tipo" class="{{ $claseFiltro }}" aria-label="{{ __('Tipo') }}">
        <option value="">{{ __('Tipo: todos') }}</option>
        @foreach ($tipos as $opcion)
            <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
        @endforeach
    </select>

    <select wire:model.live="estado" class="{{ $claseFiltro }}" aria-label="{{ __('Estado') }}">
        <option value="">{{ __('Estado: todos') }}</option>
        <option value="en_servicio">{{ __('En servicio') }}</option>
        <option value="sin_asignar">{{ __('Sin asignar') }}</option>
        @if ($conDadosDeBaja ?? true)
            <option value="dado_de_baja">{{ __('Dado de baja') }}</option>
        @endif
    </select>

    <select wire:model.live="verificacion" class="{{ $claseFiltro }}" aria-label="{{ __('Verificación') }}">
        <option value="">{{ __('Verificación: todas') }}</option>
        <option value="verificado">{{ __('Verificado') }}</option>
        <option value="pendiente_de_verificar">{{ __('Por verificar') }}</option>
    </select>

    <select wire:model.live="identificacion" class="{{ $claseFiltro }}" aria-label="{{ __('Identificación') }}">
        <option value="">{{ __('Identificación: todas') }}</option>
        <option value="sin_codigo">{{ __('Sin código de activo') }}</option>
        <option value="sin_serial">{{ __('Sin serial') }}</option>
    </select>

    <select wire:model.live="propiedad" class="{{ $claseFiltro }}" aria-label="{{ __('Propiedad') }}">
        <option value="">{{ __('Propiedad: todas') }}</option>
        <option value="gobernacion">{{ __('Gobernación') }}</option>
        <option value="tercero">{{ __('Tercero') }}</option>
    </select>

    <select wire:model.live="firmas" class="{{ $claseFiltro }}" aria-label="{{ __('Firmas') }}">
        <option value="">{{ __('Firmas: todas') }}</option>
        <option value="pendiente">{{ __('Pendiente de firma') }}</option>
        <option value="al_dia">{{ __('Firmas al día') }}</option>
    </select>

    @if ($this->hayFiltros())
        <x-ui.button variant="ghost" size="sm" wire:click="limpiarFiltros">
            {{ __('Limpiar filtros') }}
        </x-ui.button>
    @endif
</div>
