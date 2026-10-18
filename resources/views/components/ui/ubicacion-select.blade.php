{{--
    Ubicación en cadena: sede → piso de esa sede → dependencia (RF-19, RN-21).
    El piso solo ofrece los de la sede elegida, y la dependencia propone
    primero las que funcionan en ese piso según el PAMP 2026
    (ubicaciones_dependencia), sin prohibir las demás.

    Cada select usa wire:model sobre la propiedad que se indique, igual que los
    formularios existentes (propiedades ?int en el componente Livewire). Al
    cambiar la sede se vacía el piso.

    Uso:
    <x-ui.ubicacion-select sede="sedeId" piso="pisoId" dependencia="dependenciaId" />
    <x-ui.ubicacion-select sede="sedeId" piso="pisoId" dependencia="dependenciaId" :con-dependencia="false" />

    Props:
    - sede, piso, dependencia: nombres de las propiedades Livewire.
    - conDependencia: muestra el tercer select (por defecto sí).
    - columnas: clases de la grilla (por defecto tres columnas en escritorio).
--}}
@props([
    'sede',
    'piso',
    'dependencia' => null,
    'conDependencia' => true,
    'columnas' => 'grid gap-4 md:grid-cols-3',
])

@php
    $sedes = \App\Models\Sede::activas()->with(['pisos.dependencias:dependencias.id'])->orderBy('nombre')->get();
    $arbol = $conDependencia ? \App\Models\Dependencia::arbol() : collect();
    $nombres = $arbol->pluck('nombre', 'id');
    $selectClass = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
@endphp

<div
    {{ $attributes->merge(['class' => $columnas]) }}
    x-data="{
        sede: String($wire.get('{{ $sede }}') ?? ''),
        piso: String($wire.get('{{ $piso }}') ?? ''),
    }"
>
    <div>
        <label for="{{ $sede }}" class="text-[13px] font-semibold text-ink-label">{{ __('Sede') }}</label>
        <select
            id="{{ $sede }}"
            wire:model="{{ $sede }}"
            x-on:change="sede = $event.target.value; piso = ''; $wire.set('{{ $piso }}', null, false)"
            class="{{ $selectClass }}"
        >
            <option value="">{{ __('Selecciona…') }}</option>
            @foreach ($sedes as $opcion)
                <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get($sede)" class="mt-1" />
    </div>

    <div>
        <label for="{{ $piso }}" class="text-[13px] font-semibold text-ink-label">{{ __('Piso') }}</label>
        <select
            id="{{ $piso }}"
            wire:model="{{ $piso }}"
            x-on:change="piso = $event.target.value"
            x-bind:disabled="sede === ''"
            class="{{ $selectClass }} disabled:bg-app-bg"
        >
            <option value="">{{ __('Selecciona…') }}</option>
            @foreach ($sedes as $opcionSede)
                @foreach ($opcionSede->pisos as $opcionPiso)
                    <option
                        value="{{ $opcionPiso->id }}"
                        x-bind:hidden="sede !== '{{ $opcionSede->id }}'"
                        x-bind:disabled="sede !== '{{ $opcionSede->id }}'"
                    >{{ $opcionPiso->etiqueta() }}</option>
                @endforeach
            @endforeach
        </select>
        <x-input-error :messages="$errors->get($piso)" class="mt-1" />
    </div>

    @if ($conDependencia && $dependencia)
        <div>
            <label for="{{ $dependencia }}" class="text-[13px] font-semibold text-ink-label">{{ __('Dependencia') }}</label>
            <select id="{{ $dependencia }}" wire:model="{{ $dependencia }}" class="{{ $selectClass }}">
                <option value="">{{ __('Selecciona…') }}</option>
                <optgroup label="{{ __('En este piso') }}" x-bind:hidden="piso === ''">
                    @foreach ($sedes as $opcionSede)
                        @foreach ($opcionSede->pisos as $opcionPiso)
                            @foreach ($opcionPiso->dependencias as $sugerida)
                                @if ($nombres->has($sugerida->id))
                                    <option
                                        value="{{ $sugerida->id }}"
                                        x-bind:hidden="piso !== '{{ $opcionPiso->id }}'"
                                        x-bind:disabled="piso !== '{{ $opcionPiso->id }}'"
                                    >{{ $nombres[$sugerida->id] }}</option>
                                @endif
                            @endforeach
                        @endforeach
                    @endforeach
                </optgroup>
                <optgroup label="{{ __('Todas las dependencias') }}">
                    @foreach ($arbol as $opcion)
                        <option value="{{ $opcion->id }}">{{ str_repeat("\u{00A0}\u{00A0}\u{00A0}", $opcion->profundidad) }}{{ $opcion->nombre }}</option>
                    @endforeach
                </optgroup>
            </select>
            <x-input-error :messages="$errors->get($dependencia)" class="mt-1" />
        </div>
    @endif
</div>
