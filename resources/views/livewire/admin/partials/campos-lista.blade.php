{{--
    Campos propios de v4 de los catálogos de Listas (RF-45), para agregar y para
    editar. $prefijo es la propiedad de App\Livewire\Admin\ListasPanel que los
    guarda: 'extra' (agregar) o 'editandoExtra' (editar).
--}}
@php
    $claseCampo = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
@endphp

@switch($listaActiva)
    @case('tipos_equipo')
        <div class="w-full max-w-xs">
            <label for="{{ $prefijo }}-familia" class="text-[13px] font-semibold text-ink-label">{{ __('Familia') }} *</label>
            <select id="{{ $prefijo }}-familia" wire:model="{{ $prefijo }}.familia" class="{{ $claseCampo }}">
                <option value="">{{ __('Selecciona…') }}</option>
                @foreach ($familias as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get($prefijo.'.familia')" class="mt-1" />
        </div>

        <div class="w-full max-w-[200px]">
            <label for="{{ $prefijo }}-tipo_activo" class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de activo (GT-M4)') }} *</label>
            <select id="{{ $prefijo }}-tipo_activo" wire:model="{{ $prefijo }}.tipo_activo" class="{{ $claseCampo }}">
                @foreach ($tiposActivo as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get($prefijo.'.tipo_activo')" class="mt-1" />
        </div>

        <div class="w-full max-w-[160px]">
            <label for="{{ $prefijo }}-vida_util_meses" class="text-[13px] font-semibold text-ink-label">{{ __('Vida útil (meses)') }}</label>
            <input id="{{ $prefijo }}-vida_util_meses" type="number" min="1" max="600" wire:model="{{ $prefijo }}.vida_util_meses" class="{{ $claseCampo }}">
            <x-input-error :messages="$errors->get($prefijo.'.vida_util_meses')" class="mt-1" />
        </div>

        <div class="flex w-full flex-wrap gap-x-6 gap-y-2">
            <label class="flex items-center gap-2 text-[13px] text-ink">
                <input type="checkbox" wire:model="{{ $prefijo }}.usa_configuracion_computo" class="rounded border-line-input text-primary focus:ring-primary">
                {{ __('Usa configuración de cómputo (sistema operativo, red y componentes)') }}
            </label>
            <label class="flex items-center gap-2 text-[13px] text-ink">
                <input type="checkbox" wire:model="{{ $prefijo }}.activo" class="rounded border-line-input text-primary focus:ring-primary">
                {{ __('Activo (se ofrece al registrar equipos)') }}
            </label>
        </div>
        @break

    @case('sistemas_operativos')
        <div class="w-full max-w-[220px]">
            <label for="{{ $prefijo }}-fin_soporte" class="text-[13px] font-semibold text-ink-label">{{ __('Fin de soporte') }}</label>
            <input id="{{ $prefijo }}-fin_soporte" type="date" wire:model="{{ $prefijo }}.fin_soporte" class="{{ $claseCampo }}">
            <p class="mt-1 text-[12px] text-ink-muted">{{ __('Vacío = con soporte vigente. Alimenta el reporte de obsolescencia.') }}</p>
            <x-input-error :messages="$errors->get($prefijo.'.fin_soporte')" class="mt-1" />
        </div>
        @break

    @case('tipos_componente')
        <label class="flex w-full items-center gap-2 text-[13px] text-ink">
            <input type="checkbox" wire:model="{{ $prefijo }}.obligatorio_en_computo" class="rounded border-line-input text-primary focus:ring-primary">
            {{ __('Obligatorio en cómputo: sin él un equipo de cómputo no se da por verificado (RF-14)') }}
        </label>
        @break
@endswitch
