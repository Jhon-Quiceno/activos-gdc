{{--
    Vista del componente App\Livewire\Equipos\ValorarActivo (RF-56 a RF-58).
    Formulario del Manual GT-M4 (4.2.1 a 4.2.6) con el resultado en vivo.
--}}
@php
    $claseSelect = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
    $importanciaEstilos = ['baja' => 'neutral', 'media' => 'info', 'alta' => 'warning', 'muy_alta' => 'danger'];
    $siNo = \App\Livewire\Equipos\ValorarActivo::SI_NO;
@endphp

<div class="space-y-5">
    @if ($this->esSoporte())
        <p class="rounded-lg bg-warning-bg px-3 py-2 text-[13px] text-warning-text">
            {{ __('Este equipo es de tipo soporte (:tipo). Según el Manual GT-M4, los activos de soporte no se valoran (RN-25).', ['tipo' => $equipo->tipoEquipo?->nombre]) }}
        </p>
    @endif
    <x-input-error :messages="$errors->get('equipo')" />

    @if ($esReclasificacion)
        <p class="rounded-lg bg-info-bg px-3 py-2 text-[13px] text-info-text">
            {{ __('Reclasificación: el formulario trae la valoración vigente. Al guardar, esa queda en el historial y esta pasa a ser la vigente (RF-58).') }}
        </p>
    @endif

    {{-- Atributos (GT-M4 4.2.1 y 4.2.2) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="contieneDatosPersonales" class="text-[13px] font-semibold text-ink-label">{{ __('¿Contiene datos personales?') }} *</label>
            <select id="contieneDatosPersonales" wire:model.live="contieneDatosPersonales" class="{{ $claseSelect }}">
                <option value="">{{ __('Selecciona…') }}</option>
                @foreach ($siNo as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('contieneDatosPersonales')" class="mt-1" />
        </div>

        <div>
            <label for="clasificacionDatos" class="text-[13px] font-semibold text-ink-label">
                {{ __('Clasificación de los datos personales') }}@if ($contieneDatosPersonales === 'si') * @endif
            </label>
            <select
                id="clasificacionDatos"
                wire:model="clasificacionDatos"
                @disabled($contieneDatosPersonales !== 'si')
                class="{{ $claseSelect }} disabled:bg-app-bg"
            >
                <option value="">{{ __('Selecciona…') }}</option>
                @foreach ($clasificaciones as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('clasificacionDatos')" class="mt-1" />
        </div>

        <div>
            <label for="infraestructuraCritica" class="text-[13px] font-semibold text-ink-label">{{ __('¿Es infraestructura crítica?') }} *</label>
            <select id="infraestructuraCritica" wire:model="infraestructuraCritica" class="{{ $claseSelect }}">
                <option value="">{{ __('Selecciona…') }}</option>
                @foreach ($siNo as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('infraestructuraCritica')" class="mt-1" />
        </div>

        <div>
            <label for="continuidadNegocio" class="text-[13px] font-semibold text-ink-label">{{ __('¿Es clave para la continuidad del negocio?') }} *</label>
            <select id="continuidadNegocio" wire:model="continuidadNegocio" class="{{ $claseSelect }}">
                <option value="">{{ __('Selecciona…') }}</option>
                @foreach ($siNo as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('continuidadNegocio')" class="mt-1" />
        </div>
    </div>

    {{-- Propiedad y ubicación (GT-M4 4.2.3 y 4.2.4) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-ui.input name="propietarioCargo" :label="__('Propietario del activo (cargo)') . ' *'" wire:model="propietarioCargo" maxlength="120" :placeholder="__('Director o líder del proceso')" />
        </div>
        <div>
            <x-ui.input name="custodioCargo" :label="__('Custodio del activo (cargo)') . ' *'" wire:model="custodioCargo" maxlength="120" :placeholder="__('Cargo de quien lo tiene a cargo')" />
        </div>

        <div>
            <label for="tipoUbicacion" class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de ubicación') }} *</label>
            <select id="tipoUbicacion" wire:model="tipoUbicacion" class="{{ $claseSelect }}">
                @foreach (\App\Livewire\Equipos\ValorarActivo::TIPOS_UBICACION as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('tipoUbicacion')" class="mt-1" />
        </div>

        <x-ui.input name="tiempoRetencion" :label="__('Tiempo de retención de los datos')" wire:model="tiempoRetencion" maxlength="40" :placeholder="__('Ej.: 5 años, Indeterminado')" />
    </div>

    {{-- Valoración C-I-D (GT-M4 4.2.5, tablas 3 a 5) --}}
    @foreach ($escalas as $criterio => $escala)
        <fieldset wire:key="escala-{{ $criterio }}">
            <legend class="text-[14px] font-semibold text-ink">{{ $escala['titulo'] }} *</legend>
            <p class="text-[13px] text-ink-muted">{{ $escala['pregunta'] }}</p>
            <div class="mt-2 grid grid-cols-1 gap-2">
                @foreach ($escala['niveles'] as $nivel => $texto)
                    <label @class([
                        'flex cursor-pointer items-start gap-3 rounded-lg border px-3 py-2 text-[13px] transition-colors duration-150',
                        'border-primary bg-info-bg' => (int) $this->{$criterio} === $nivel,
                        'border-line hover:border-primary' => (int) $this->{$criterio} !== $nivel,
                    ])>
                        <input type="radio" wire:model.live="{{ $criterio }}" value="{{ $nivel }}" class="mt-0.5 border-line-input text-primary focus:ring-primary">
                        <span><span class="font-semibold text-ink">{{ $nivel }}</span> · {{ __($texto) }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get($criterio)" class="mt-1" />
        </fieldset>
    @endforeach

    {{-- Controles de monitoreo (hoja «Datos» del FOR.01) --}}
    <fieldset>
        <legend class="text-[14px] font-semibold text-ink">{{ __('Controles de monitoreo') }}</legend>
        <div class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
            @foreach ($controles as $control)
                <label class="flex items-center gap-2 text-[13px] text-ink">
                    <input type="checkbox" wire:model="controlesMonitoreo" value="{{ $control }}" class="rounded border-line-input text-primary focus:ring-primary">
                    {{ __($control) }}
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- Resultado en vivo (GT-M4 tabla 6 y 4.2.6) --}}
    <div class="rounded-lg border border-line bg-app-bg px-4 py-3" aria-live="polite">
        @if ($resultado['completo'])
            <div class="flex flex-wrap items-center gap-3 text-[14px]">
                <span class="text-ink-muted">{{ __('C :c + I :i + D :d =', ['c' => $confidencialidad, 'i' => $integridad, 'd' => $disponibilidad]) }}</span>
                <span class="font-display text-[22px] font-semibold text-ink">{{ $resultado['total'] }}</span>
                <x-ui.badge :variant="$importanciaEstilos[$resultado['importancia']] ?? 'neutral'">
                    {{ __('Importancia :nivel', ['nivel' => __($importancias[$resultado['importancia']])]) }}
                </x-ui.badge>
                @if ($resultado['requiere_gestion_riesgo'])
                    <x-ui.badge variant="danger">{{ __('Pasa a gestión de riesgos') }}</x-ui.badge>
                @else
                    <x-ui.badge variant="success">{{ __('No requiere gestión de riesgos') }}</x-ui.badge>
                @endif
            </div>
            @if ($resultado['requiere_gestion_riesgo'])
                <p class="mt-1 text-[13px] text-ink-muted">{{ __('Algún criterio vale 4 o la importancia es alta o muy alta (GT-M4 4.2.6).') }}</p>
            @endif
        @else
            <p class="text-[13px] text-ink-muted">{{ __('Elige los tres niveles para ver el total, la importancia y si pasa a gestión de riesgos.') }}</p>
        @endif
    </div>

    <div class="flex flex-wrap justify-end gap-2">
        <x-ui.button variant="secondary" type="button" x-on:click="$dispatch('close-modal', 'valorar-activo')">
            {{ __('Cancelar') }}
        </x-ui.button>
        <x-ui.button variant="primary" type="button" wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar">
            {{ $esReclasificacion ? __('Guardar reclasificación') : __('Guardar valoración') }}
        </x-ui.button>
    </div>
</div>
