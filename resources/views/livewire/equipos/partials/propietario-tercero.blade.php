{{--
    Propietario y figura de un equipo de tercero (RF-04, RN-16). El propietario
    sale del catálogo de proveedores; si no está, se escribe uno nuevo y se crea
    al guardar. Lo usan el registro (page-crear) y la edición (page-editar).

    Espera $proveedores y $figurasTercero, y las propiedades $proveedorTerceroId,
    $agregarProveedor, $proveedorNuevo, $proveedorNuevoNit y $figura.
--}}
<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        @if ($agregarProveedor)
            <x-ui.input name="proveedorNuevo" :label="__('Propietario nuevo (razón social)') . ' *'" wire:model="proveedorNuevo" maxlength="160" />
            <x-ui.input name="proveedorNuevoNit" :label="__('NIT')" wire:model="proveedorNuevoNit" maxlength="20" class="mt-3" :placeholder="__('Opcional')" />
        @else
            <label for="proveedorTerceroId" class="text-[13px] font-semibold text-ink-label">{{ __('Propietario') }} *</label>
            <select id="proveedorTerceroId" wire:model="proveedorTerceroId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                <option value="">{{ __('Selecciona…') }}</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}">{{ $proveedor->razon_social }}@if ($proveedor->nit) · NIT {{ $proveedor->nit }}@endif</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('proveedorTerceroId')" class="mt-1" />
        @endif

        <label class="mt-2 flex items-center gap-2 text-[13px] text-ink-muted">
            <input type="checkbox" wire:model.live="agregarProveedor" class="rounded border-line-input text-primary focus:ring-primary">
            {{ __('El propietario no está en la lista: agregarlo') }}
        </label>
    </div>

    <div>
        <label for="figura" class="text-[13px] font-semibold text-ink-label">{{ __('Figura') }} *</label>
        <select id="figura" wire:model="figura" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
            <option value="">{{ __('Selecciona…') }}</option>
            @foreach ($figurasTercero as $valor => $etiqueta)
                <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('figura')" class="mt-1" />
    </div>
</div>
