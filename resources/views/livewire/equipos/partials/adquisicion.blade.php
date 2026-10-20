{{--
    Datos de adquisición (RF-52), trait DatosDeAdquisicion. Lo usan el
    registro (page-crear) y la edición (page-editar).

    Espera $titulo, $formasIngreso, $proveedores y, opcional, $vidaUtilDelTipo
    (meses que trae el tipo de equipo, para el texto de ayuda).
--}}
@php
    $claseSelect = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
@endphp
<x-ui.card>
    <p class="section-title">{{ $titulo }}</p>
    <p class="mt-1 text-[13px] text-ink-muted">{{ __('Opcional, salvo la forma de ingreso. Con la fecha y la garantía, la hoja de vida avisa cuando la garantía está por vencer o el equipo supera su vida útil.') }}</p>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="formaIngreso" class="text-[13px] font-semibold text-ink-label">{{ __('Forma de ingreso') }} *</label>
            <select id="formaIngreso" wire:model="formaIngreso" class="{{ $claseSelect }}">
                @foreach ($formasIngreso as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('formaIngreso')" class="mt-1" />
        </div>

        <x-ui.input name="fechaAdquisicion" type="date" :label="__('Fecha de adquisición')" wire:model="fechaAdquisicion" :max="now()->toDateString()" />

        <div>
            <x-ui.input name="valorAdquisicion" :label="__('Valor (COP)')" wire:model.blur="valorAdquisicion" inputmode="decimal" placeholder="2.350.000" />
        </div>

        <div>
            <label for="proveedorId" class="text-[13px] font-semibold text-ink-label">{{ __('Proveedor') }}</label>
            <select id="proveedorId" wire:model="proveedorId" class="{{ $claseSelect }}">
                <option value="">{{ __('Sin proveedor registrado') }}</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}">{{ $proveedor->razon_social }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('proveedorId')" class="mt-1" />
        </div>

        <x-ui.input name="numeroContrato" :label="__('N.º de contrato')" wire:model="numeroContrato" maxlength="40" />
        <x-ui.input name="numeroFactura" :label="__('N.º de factura')" wire:model="numeroFactura" maxlength="40" />

        <x-ui.input name="fechaFinGarantia" type="date" :label="__('Fin de la garantía')" wire:model="fechaFinGarantia" />

        <div>
            <x-ui.input name="vidaUtilMeses" type="number" min="1" max="600" :label="__('Vida útil (meses)')" wire:model="vidaUtilMeses" />
            @if (! empty($vidaUtilDelTipo))
                <p class="mt-1 text-[13px] text-ink-muted">{{ __('Si lo dejas vacío se usan los :meses meses del tipo de equipo.', ['meses' => $vidaUtilDelTipo]) }}</p>
            @endif
        </div>
    </div>
</x-ui.card>
