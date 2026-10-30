<div class="space-y-6">
    <x-ui.page-header
        :title="__('Cambio de componente')"
        :subtitle="trim(($equipo->tipoEquipo?->nombre ?? '') . ' · ' . ($equipo->marca?->nombre ?? '') . ' ' . ($equipo->modeloEquipo?->nombre ?? '') . ' · ' . $equipo->serial . ($equipo->codigo_activo ? ' · ' . $equipo->codigo_activo : ''))"
    />

    <x-input-error :messages="$errors->get('equipo')" />

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
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('De la configuración actual') }}</label>
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
                    <label for="destino-retirado" class="text-[13px] font-semibold text-ink-label">{{ __('Destino de lo retirado') }}</label>
                    <select id="destino-retirado" wire:model.live="destinoRetirado" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach ($destinos as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('destinoRetirado')" class="mt-1" />
                </div>

                @if ($destinoRetirado === 'otro_equipo')
                    {{-- RF-17: el componente pasa a la configuración del equipo destino, con su propio evento. --}}
                    <div class="mt-4">
                        <label for="busqueda-destino" class="text-[13px] font-semibold text-ink-label">{{ __('Equipo destino') }}</label>
                        <input
                            id="busqueda-destino"
                            type="search"
                            wire:model.live.debounce.400ms="busquedaDestino"
                            placeholder="{{ __('Serial o código de activo (I1-######)') }}"
                            autocomplete="off"
                            class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink placeholder:text-ink-muted focus:border-primary focus:ring-primary"
                        >

                        @if ($equipoDestino)
                            <div class="mt-2 rounded-lg border border-line bg-app-bg px-3 py-2 text-[13px]" wire:key="destino-{{ $equipoDestino->id }}">
                                <p class="font-semibold text-ink">
                                    {{ $equipoDestino->tipoEquipo?->nombre }} · {{ $equipoDestino->marca?->nombre }} {{ $equipoDestino->modeloEquipo?->nombre }}
                                </p>
                                <p class="font-mono text-ink-muted">
                                    {{ $equipoDestino->serial }}@if($equipoDestino->codigo_activo) · {{ $equipoDestino->codigo_activo }}@endif
                                </p>
                                <p class="text-ink-muted">
                                    {{ $equipoDestino->asignacionActual?->persona?->nombreCompleto() ?? __('Sin asignar') }}
                                    @if($equipoDestino->asignacionActual?->piso) · {{ $equipoDestino->asignacionActual->piso->sede?->nombre }}@endif
                                </p>
                                @if ($destinoDadoDeBaja)
                                    <p class="mt-1 font-semibold text-danger-text">{{ __('Este equipo está dado de baja: no puede recibir componentes.') }}</p>
                                @endif
                            </div>
                        @elseif (trim($busquedaDestino) !== '')
                            <p class="mt-2 text-[13px] text-ink-muted">{{ __('No hay ningún equipo con ese serial o código de activo.') }}</p>
                        @endif

                        <x-input-error :messages="$errors->get('equipoDestinoId')" class="mt-1" />
                    </div>
                @endif
            </x-ui.card>
        @endif

        @if ($accion !== 'quitar')
            <x-ui.card>
                <p class="section-title">{{ __('Componente que entra') }}</p>

                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo') }}</label>
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
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Motivo') }}</label>
                <textarea wire:model="motivo" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
                <x-input-error :messages="$errors->get('motivo')" class="mt-1" />
            </div>

            <x-ui.input type="date" name="fecha" :label="__('Fecha')" wire:model="fecha" max="{{ now()->toDateString() }}" />
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
