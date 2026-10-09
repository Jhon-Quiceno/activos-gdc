{{--
    Vista propia del componente Livewire App\Livewire\Equipos\Editar.
    Se llama "page-editar" por la misma razón que page-crear: la ruta
    `equipos.editar` apunta a 'livewire.equipos.editar', que solo envuelve
    este componente en el layout.
--}}
<div>
    @if($bloqueado)
        <div class="mb-4 rounded-lg bg-danger-bg px-4 py-3 text-[14px] text-danger-text">
            {{ __('Este equipo está dado de baja: conserva su hoja de vida, pero no admite cambios (RN-10). Si la baja fue un error, anúlala primero.') }}
        </div>
    @endif
    <x-input-error :messages="$errors->get('equipo')" class="mb-4" />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            {{-- 1. Tipo de equipo (no se edita) --}}
            <x-ui.card>
                <p class="section-title">{{ __('1. Tipo de equipo') }}</p>
                <p class="mt-2 text-[14px] text-ink">{{ $equipo->tipoEquipo?->nombre }}</p>
                <p class="mt-1 text-[13px] text-ink-muted">
                    {{ __('El tipo no se cambia al editar: cambiaría toda la ficha. Si se registró con el tipo equivocado, deja una aclaración en la hoja de vida.') }}
                </p>
            </x-ui.card>

            {{-- 2. Identificación --}}
            <x-ui.card>
                <p class="section-title">{{ __('2. Identificación') }}</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input name="serial" :label="__('Serial del fabricante') . ' *'" wire:model.blur="serial" required />

                    <div>
                        <x-ui.input
                            name="codigoActivo"
                            :label="__('Código de activo') . ($sinCodigoActivo ? '' : ' *')"
                            wire:model.blur="codigoActivo"
                            placeholder="I1-000000"
                            :disabled="$sinCodigoActivo"
                        />
                        @unless ($sinCodigoActivo)
                            <p class="mt-1 text-[13px] text-ink-muted">{{ __('Formato I1-######. Se corrige solo: «I1 24147» queda «I1-24147».') }}</p>
                        @endunless
                        @if ($otroConMismoCodigo = $this->equipoConMismoCodigo())
                            <div class="mt-2 rounded-lg bg-warning-bg px-3 py-2 text-[13px] text-warning-text">
                                {{ __('Este código ya está en otro equipo: :tipo con serial :serial. Si es correcto (por ejemplo, un All in One que comparte código con su pantalla), explica por qué.', [
                                    'tipo' => $otroConMismoCodigo->tipoEquipo?->nombre ?? __('equipo'),
                                    'serial' => $otroConMismoCodigo->serial,
                                ]) }}
                            </div>
                            <label for="codigoActivoJustificacion" class="mt-2 block text-[13px] font-semibold text-ink-label">{{ __('Justificación del código repetido') }} *</label>
                            <textarea
                                id="codigoActivoJustificacion"
                                wire:model="codigoActivoJustificacion"
                                rows="2"
                                class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"
                            ></textarea>
                            <x-input-error :messages="$errors->get('codigoActivoJustificacion')" class="mt-1" />
                        @endif
                        <label class="mt-2 flex items-center gap-2 text-[13px] text-ink-muted">
                            <input type="checkbox" wire:model.live="sinCodigoActivo" class="rounded border-line-input text-primary focus:ring-primary">
                            {{ __('El equipo no tiene código de activo') }}
                        </label>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Marca') }} *</label>
                        <select wire:model="marcaId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('marcaId')" class="mt-1" />
                    </div>

                    <x-ui.input name="modelo" :label="__('Modelo')" wire:model="modelo" />
                </div>

                <div class="mt-4 max-w-sm">
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Estado de funcionamiento') }} *</label>
                    <select wire:model="estadoFuncionamiento" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach (['Bueno', 'Regular', 'Malo, requiere cambio de componentes', 'No funcional'] as $estado)
                            <option value="{{ $estado }}">{{ __($estado) }}</option>
                        @endforeach
                        @if ($estadoFuncionamiento !== '' && ! in_array($estadoFuncionamiento, ['Bueno', 'Regular', 'Malo, requiere cambio de componentes', 'No funcional'], true))
                            {{-- Valor que no está en la lista (p. ej. importado del inventario): se conserva. --}}
                            <option value="{{ $estadoFuncionamiento }}">{{ $estadoFuncionamiento }}</option>
                        @endif
                    </select>
                    <x-input-error :messages="$errors->get('estadoFuncionamiento')" class="mt-1" />
                </div>
            </x-ui.card>

            {{-- 3. Propiedad --}}
            <x-ui.card>
                <p class="section-title">{{ __('3. Propiedad') }}</p>

                <div class="mt-3">
                    <x-ui.segmented-control
                        :options="['gobernacion' => __('Gobernación'), 'tercero' => __('Tercero')]"
                        :selected="$propiedad"
                        model="propiedad"
                    />
                </div>

                @if ($propiedad === 'tercero')
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input name="propietarioTercero" :label="__('Propietario') . ' *'" wire:model="propietarioTercero" required />

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Figura') }} *</label>
                            <select wire:model="figura" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="comodato">{{ __('Comodato') }}</option>
                                <option value="convenio">{{ __('Convenio') }}</option>
                                <option value="proveedor">{{ __('Proveedor') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('figura')" class="mt-1" />
                        </div>
                    </div>
                @endif
            </x-ui.card>

            {{-- 4. Software (cómputo) o características (demás familias) --}}
            @if ($familiaSeleccionada === 'computo')
                <x-ui.card>
                    <p class="section-title">{{ __('4. Software') }}</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Sistema operativo') }}</label>
                            <select wire:model="sistemaOperativoId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($sistemasOperativos as $so)
                                    <option value="{{ $so->id }}">{{ $so->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('¿Tiene antivirus?') }}</label>
                            <select wire:model.live="tieneAntivirus" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="no">{{ __('No') }}</option>
                                <option value="si">{{ __('Sí') }}</option>
                            </select>
                        </div>

                        @if ($tieneAntivirus === 'si')
                            <x-ui.input name="antivirusProducto" :label="__('Producto antivirus')" wire:model="antivirusProducto" />
                        @endif

                        <x-ui.input name="nombreRed" :label="__('Nombre de red')" wire:model="nombreRed" />
                    </div>

                    <p class="mt-4 rounded-lg bg-info-bg px-3 py-2 text-[13px] text-info-text">
                        {{ __('Procesador, RAM, disco y periféricos no se editan aquí: se cambian con «Cambio de componente», que deja el registro de lo retirado y lo instalado (RN-07).') }}
                    </p>
                </x-ui.card>
            @else
                @include('livewire.equipos.partials.caracteristicas')
            @endif

            {{-- 5. Observaciones --}}
            <x-ui.card>
                <p class="section-title">{{ __('5. Observaciones') }}</p>
                <textarea
                    wire:model="observaciones"
                    rows="3"
                    class="mt-3 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"
                ></textarea>
                <x-input-error :messages="$errors->get('observaciones')" class="mt-1" />
            </x-ui.card>
        </div>

        {{-- Resumen --}}
        <div class="lg:col-span-1">
            <div class="lg:sticky lg:top-6">
                <x-ui.card>
                    <p class="section-title">{{ __('Al guardar') }}</p>

                    <ul class="mt-3 list-disc space-y-1 pl-4 text-[13px] text-ink-muted">
                        <li>{{ __('Se registra el evento «Actualización de datos» a nombre de :usuario, con el valor anterior y el nuevo de cada campo cambiado.', ['usuario' => auth()->user()->name]) }}</li>
                        <li>{{ __('Si no cambias nada, no se registra ningún evento.') }}</li>
                        <li>{{ __('El responsable y la ubicación se cambian con «Trasladar / reasignar».') }}</li>
                    </ul>

                    <div class="mt-5 space-y-2">
                        <x-ui.button variant="primary" wire:click="guardar" class="w-full justify-center" :disabled="$bloqueado">
                            {{ __('Guardar cambios') }}
                        </x-ui.button>
                        <x-ui.button variant="ghost" :href="route('equipos.show', $equipo)" class="w-full justify-center">
                            {{ __('Cancelar') }}
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</div>
