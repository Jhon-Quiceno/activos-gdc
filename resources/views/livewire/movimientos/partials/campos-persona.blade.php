{{--
    Campos de la persona responsable (RF-18), trait
    App\Livewire\Movimientos\Concerns\FormularioPersona. Lo usan Responsables
    (crear y editar) y el traslado (persona nueva).

    Espera $cargos, $tiposDocumento, $vinculaciones y $provisiones.
--}}
@php
    $claseSelect = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
@endphp
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-ui.input name="nombres" :label="__('Nombres') . ' *'" wire:model="nombres" maxlength="80" required />
    <x-ui.input name="apellidos" :label="__('Apellidos')" wire:model="apellidos" maxlength="80" />

    <div>
        <label for="tipoDocumento" class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de documento') }} *</label>
        <select id="tipoDocumento" wire:model.live="tipoDocumento" class="{{ $claseSelect }}">
            @foreach ($tiposDocumento as $valor => $etiqueta)
                <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
            @endforeach
        </select>
    </div>
    <x-ui.input name="numeroDocumento" :label="__('Número de documento') . ' *'" wire:model.blur="numeroDocumento" :inputmode="$tipoDocumento === 'CC' ? 'numeric' : 'text'" maxlength="20" required />

    <div>
        <label for="cargoId" class="text-[13px] font-semibold text-ink-label">{{ __('Cargo (Decreto 607)') }}</label>
        <select id="cargoId" wire:model.live="cargoId" class="{{ $claseSelect }}">
            <option value="">{{ __('Sin cargo') }}</option>
            @foreach ($cargos as $cargo)
                <option value="{{ $cargo->id }}">{{ $cargo->etiqueta() }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('cargoId')" class="mt-1" />
    </div>
    <div class="grid grid-cols-2 gap-3">
        <x-ui.input name="grado" :label="__('Grado')" wire:model="grado" inputmode="numeric" maxlength="2" />
        @if ($cargoId)
            <label class="mt-7 flex items-center gap-2 text-[13px] text-ink">
                <input type="checkbox" wire:model="encargo" class="rounded border-line-input text-primary focus:ring-primary">
                {{ __('En encargo (E)') }}
            </label>
        @endif
    </div>

    <x-ui.input type="email" name="correoInstitucional" :label="__('Correo institucional')" wire:model="correoInstitucional" maxlength="120" />

    <div>
        <label for="tipoVinculacion" class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de vinculación') }} *</label>
        <select id="tipoVinculacion" wire:model.live="tipoVinculacion" class="{{ $claseSelect }}">
            <option value="">{{ __('Selecciona…') }}</option>
            @foreach ($vinculaciones as $valor => $etiqueta)
                <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipoVinculacion')" class="mt-1" />
    </div>

    <div>
        <label for="tipoProvision" class="text-[13px] font-semibold text-ink-label">{{ __('Provisión') }}</label>
        <select id="tipoProvision" wire:model="tipoProvision" class="{{ $claseSelect }}">
            @foreach ($provisiones as $valor => $etiqueta)
                <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
            @endforeach
        </select>
    </div>

    @if ($tipoVinculacion === 'contratista')
        <x-ui.input name="numeroContrato" :label="__('Número de contrato') . ' *'" wire:model="numeroContrato" maxlength="40" />
    @endif

    <div>
        <x-ui.input type="date" name="fechaFinVinculacion" :label="__('Fin de la vinculación')" wire:model="fechaFinVinculacion" />
        <p class="mt-1 text-[12px] text-ink-muted">{{ __('Fin del contrato o fecha de retiro. Avisa el paz y salvo (RF-23).') }}</p>
    </div>
</div>
