<div class="space-y-6">
    <x-ui.page-header
        :title="__('Cambio de componente')"
        :subtitle="trim(($equipo->tipoEquipo?->nombre ?? '') . ' · ' . ($equipo->marca?->nombre ?? '') . ' ' . ($equipo->modelo ?? '') . ' · ' . $equipo->serial . ($equipo->codigo_activo ? ' · ' . $equipo->codigo_activo : ''))"
    />

    <div>
        <x-ui.segmented-control
            :options="['agregar' => __('Agregar'), 'cambiar' => __('Cambiar'), 'quitar' => __('Quitar')]"
            :selected="$accion"
            model="accion"
        />
        <p class="mt-2 text-[13px] text-ink-muted">
            {{ __('El mantenimiento no se registra aquí; solo los cambios de componentes.') }}
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 nav:grid-cols-2">
        @if ($accion !== 'agregar')
            <x-ui.card>
                <p class="section-title">{{ __('Componente que sale') }}</p>

                <div class="mt-3">
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Componente') }} *</label>
                    <select wire:model="componenteRetiradoId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach ($equipo->componentes as $componente)
                            <option value="{{ $componente->id }}">
                                {{ $componente->tipoComponente?->nombre }}
                                @if($componente->capacidad_caracteristica) · {{ $componente->capacidad_caracteristica }} @endif
                                @if($componente->serial) · {{ $componente->serial }} @endif
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('componenteRetiradoId')" class="mt-1" />
                </div>

                <div class="mt-4">
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Destino de lo retirado') }} *</label>
                    <select wire:model="destinoRetirado" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Selecciona…') }}</option>
                        <option value="bodega">{{ __('Bodega') }}</option>
                        <option value="otro_equipo">{{ __('Otro equipo') }}</option>
                        <option value="descarte">{{ __('Descarte') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('destinoRetirado')" class="mt-1" />
                </div>
            </x-ui.card>
        @endif

        @if ($accion !== 'quitar')
            <x-ui.card>
                <p class="section-title">{{ __('Componente que entra') }}</p>

                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo') }} *</label>
                        <select wire:model="tipoComponenteId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($tiposComponente as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tipoComponenteId')" class="mt-1" />
                    </div>

                    <x-ui.input name="caracteristica" :label="__('Característica')" wire:model="caracteristica" />
                    <x-ui.input name="marca" :label="__('Marca')" wire:model="marca" />
                    <x-ui.input name="serial" :label="__('Serial')" wire:model="serial" />
                </div>
            </x-ui.card>
        @endif
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Motivo') }} *</label>
                <textarea wire:model="motivo" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
                <x-input-error :messages="$errors->get('motivo')" class="mt-1" />
            </div>

            <x-ui.input type="date" name="fecha" :label="__('Fecha')" wire:model="fecha" />
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button variant="secondary" :href="route('equipos.show', $equipo)">
            {{ __('Cancelar') }}
        </x-ui.button>
        <x-ui.button variant="primary" wire:click="guardar">
            {{ __('Registrar cambio') }}
        </x-ui.button>
    </div>
</div>
