{{--
    Selector de dependencia en árbol (RF-60), con buscador sin tildes.
    Muestra las dependencias del Decreto 609 con sangría según su nivel
    (Despacho → Secretaría → Dirección → Área). Para filtrar «todo lo de una
    secretaría» en una consulta, usar Dependencia::idsDeRama($id).

    Uso:
    <x-ui.dependencia-select name="dependenciaId" label="Dependencia" wire:model="dependenciaId" />
    <x-ui.dependencia-select name="dependenciaId" wire:model.live="dependenciaId" placeholder="Todas" :buscador="false" />

    Props:
    - name: nombre del campo (para el id y los errores de validación).
    - label: texto del label (opcional).
    - placeholder: texto de la opción vacía.
    - soloActivas: oculta las dependencias desactivadas (por defecto sí).
    - buscador: muestra el cuadro de búsqueda (por defecto sí).
--}}
@props([
    'name',
    'label' => null,
    'placeholder' => 'Selecciona…',
    'soloActivas' => true,
    'buscador' => true,
])

@php
    $opciones = \App\Models\Dependencia::arbol($soloActivas);
    $normalizar = fn (?string $texto) => \App\Models\DependenciaEquivalencia::normalizar((string) $texto);
@endphp

<div {{ $attributes->only('class') }} x-data="{ filtro: '' }">
    @if ($label)
        <label for="{{ $name }}" class="text-[13px] font-semibold text-ink-label">{{ $label }}</label>
    @endif

    @if ($buscador)
        <input
            type="search"
            x-model="filtro"
            placeholder="{{ __('Buscar dependencia…') }}"
            aria-label="{{ __('Buscar dependencia') }}"
            class="mt-1 block h-9 w-full rounded-lg border-line-input text-[13px] text-ink focus:border-primary focus:ring-primary"
        />
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->except(['class', 'name', 'label', 'placeholder', 'soloActivas', 'buscador']) }}
        class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($opciones as $dependencia)
            @php($texto = $normalizar($dependencia->nombre.' '.$dependencia->nombre_corto.' '.$dependencia->sigla))
            <option
                value="{{ $dependencia->id }}"
                data-texto="{{ $texto }}"
                x-bind:hidden="filtro !== '' && ! $el.dataset.texto.includes(filtro.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''))"
            >{{ str_repeat("\u{00A0}\u{00A0}\u{00A0}", $dependencia->profundidad) }}{{ $dependencia->nombre }}</option>
        @endforeach
    </select>

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
