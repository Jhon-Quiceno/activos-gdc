{{--
    Marca y modelo del catálogo (docs/base-de-datos.md §4.4). Lo usan el
    registro (page-crear) y la edición (page-editar), con el trait
    CatalogoDelEquipo. El modelo depende de la marca y del tipo de equipo; si
    no está en el catálogo, se escribe uno nuevo y se crea al guardar.

    Espera $marcas y $modelosDisponibles, y las propiedades $marcaId,
    $tipoEquipoId, $modeloId, $agregarModelo y $modeloNuevo del componente.
--}}
<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label for="marcaId" class="text-[13px] font-semibold text-ink-label">{{ __('Marca') }} *</label>
        <select id="marcaId" wire:model.live="marcaId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
            <option value="">{{ __('Selecciona…') }}</option>
            @foreach ($marcas as $marca)
                <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('marcaId')" class="mt-1" />
    </div>

    <div>
        @if ($agregarModelo)
            <x-ui.input name="modeloNuevo" :label="__('Modelo nuevo') . ' *'" wire:model="modeloNuevo" maxlength="100" :placeholder="__('Como aparece en la etiqueta del equipo')" />
        @else
            <label for="modeloId" class="text-[13px] font-semibold text-ink-label">{{ __('Modelo') }}</label>
            <select
                id="modeloId"
                wire:model.live="modeloId"
                @disabled(! $marcaId || ! $tipoEquipoId)
                class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary disabled:bg-app-bg"
            >
                @if (! $tipoEquipoId)
                    <option value="">{{ __('Elige primero el tipo') }}</option>
                @elseif (! $marcaId)
                    <option value="">{{ __('Elige primero la marca') }}</option>
                @else
                    <option value="">{{ $modelosDisponibles->isEmpty() ? __('No hay modelos de esta marca para este tipo') : __('Selecciona…') }}</option>
                    @foreach ($modelosDisponibles as $opcion)
                        <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
                    @endforeach
                @endif
            </select>
            <x-input-error :messages="$errors->get('modeloId')" class="mt-1" />
        @endif

        @if ($marcaId && $tipoEquipoId)
            <label class="mt-2 flex items-center gap-2 text-[13px] text-ink-muted">
                <input type="checkbox" wire:model.live="agregarModelo" class="rounded border-line-input text-primary focus:ring-primary">
                {{ __('El modelo no está en la lista: escribir uno nuevo') }}
            </label>
        @endif
    </div>
</div>
