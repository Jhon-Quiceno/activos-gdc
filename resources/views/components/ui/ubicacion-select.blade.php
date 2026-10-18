{{--
    Ubicación en cadena: sede → piso de esa sede → dependencia de esa sede (RF-19, RN-21).

    - Al elegir la sede, el piso solo ofrece los pisos de esa sede y la
      dependencia solo las que funcionan en esa sede según el PAMP 2026
      (ubicaciones_dependencia).
    - Al elegir el piso, la dependencia se reduce a las de ese piso.
    - Salida: el enlace «Ver todas las dependencias» abre la lista completa,
      para oficinas que se mudaron o dependencias que el PAMP no ubica. Si el
      valor guardado (por ejemplo, al editar) no está en la sede, la lista
      completa se abre sola para no ocultarlo.
    - Si al cambiar la sede o el piso la dependencia elegida deja de estar en
      la lista, se vacía; el piso se vacía siempre que cambia la sede.

    Cada select usa wire:model sobre la propiedad indicada, igual que los
    formularios existentes (propiedades ?int en el componente Livewire).

    Uso:
    <x-ui.ubicacion-select sede="sedeId" piso="pisoId" dependencia="dependenciaId" />
    <x-ui.ubicacion-select sede="sedeId" piso="pisoId" />   (sin dependencia, p. ej. bodega)

    Props:
    - sede, piso, dependencia: nombres de las propiedades Livewire.
    - columnas: clases de la grilla (por defecto tres columnas en escritorio).
--}}
@props([
    'sede',
    'piso',
    'dependencia' => null,
    'columnas' => 'grid gap-4 md:grid-cols-3',
])

@php
    $sedes = \App\Models\Sede::activas()->with(['pisos.dependencias:dependencias.id'])->orderBy('nombre')->get();
    $arbol = $dependencia ? \App\Models\Dependencia::arbol() : collect();

    // Ids (como texto, igual que el valor de un <option>) de las dependencias de cada sede y de cada piso.
    $porPiso = [];
    $porSede = [];
    foreach ($sedes as $s) {
        foreach ($s->pisos as $p) {
            $ids = $p->dependencias->pluck('id')->map(fn ($id) => (string) $id)->all();
            $porPiso[(string) $p->id] = $ids;
            $porSede[(string) $s->id] = array_values(array_unique(array_merge($porSede[(string) $s->id] ?? [], $ids)));
        }
    }

    $selectClass = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
@endphp

<div
    {{ $attributes->merge(['class' => $columnas]) }}
    x-data="{
        sede: String($wire.get('{{ $sede }}') ?? ''),
        piso: String($wire.get('{{ $piso }}') ?? ''),
        dependencia: @if ($dependencia) String($wire.get('{{ $dependencia }}') ?? '') @else '' @endif,
        porSede: @js($porSede),
        porPiso: @js($porPiso),
        todas: false,
        init() {
            if (this.dependencia !== '' && ! this.disponibles().includes(this.dependencia)) {
                this.todas = true;
            }
        },
        disponibles() {
            if (this.piso !== '') return this.porPiso[this.piso] ?? [];
            if (this.sede !== '') return this.porSede[this.sede] ?? [];
            return [];
        },
        visible(id) {
            return this.todas || this.disponibles().includes(id);
        },
        revisarDependencia() {
            @if ($dependencia)
                if (! this.todas && this.dependencia !== '' && ! this.visible(this.dependencia)) {
                    this.dependencia = '';
                    this.$wire.set('{{ $dependencia }}', null, false);
                }
            @endif
        },
    }"
>
    <div>
        <label for="{{ $sede }}" class="text-[13px] font-semibold text-ink-label">{{ __('Sede') }}</label>
        <select
            id="{{ $sede }}"
            wire:model="{{ $sede }}"
            x-on:change="sede = $event.target.value; piso = ''; $wire.set('{{ $piso }}', null, false); revisarDependencia()"
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
            x-on:change="piso = $event.target.value; revisarDependencia()"
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

    @if ($dependencia)
        <div>
            <label for="{{ $dependencia }}" class="text-[13px] font-semibold text-ink-label">{{ __('Dependencia') }}</label>
            <select
                id="{{ $dependencia }}"
                wire:model="{{ $dependencia }}"
                x-on:change="dependencia = $event.target.value"
                x-bind:disabled="! todas && sede === ''"
                class="{{ $selectClass }} disabled:bg-app-bg"
            >
                <option value="" x-text="! todas && sede === '' ? '{{ __('Elige primero la sede') }}' : '{{ __('Selecciona…') }}'">{{ __('Elige primero la sede') }}</option>
                @foreach ($arbol as $opcion)
                    <option
                        value="{{ $opcion->id }}"
                        data-arbol="{{ str_repeat("\u{00A0}\u{00A0}\u{00A0}", $opcion->profundidad) }}{{ $opcion->nombre }}"
                        data-plano="{{ $opcion->nombre }}"
                        x-bind:hidden="! visible('{{ $opcion->id }}')"
                        x-bind:disabled="! visible('{{ $opcion->id }}')"
                        x-text="todas ? $el.dataset.arbol : $el.dataset.plano"
                    >{{ $opcion->nombre }}</option>
                @endforeach
            </select>

            <button
                type="button"
                class="mt-1 text-[13px] text-primary hover:underline"
                x-show="sede !== '' || todas"
                x-on:click="todas = ! todas; revisarDependencia()"
                x-text="todas ? '{{ __('Ver solo las de esta sede') }}' : '{{ __('¿No está? Ver todas las dependencias') }}'"
            >{{ __('¿No está? Ver todas las dependencias') }}</button>

            <x-input-error :messages="$errors->get($dependencia)" class="mt-1" />
        </div>
    @endif
</div>
